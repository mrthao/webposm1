/*
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Webpos
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

define(
    [
        'jquery',
        'ko',
        'ui/components/order/action',
        'model/sales/order/total',
        'helper/general',
        'helper/datetime',
        'action/notification/add-notification',
        'model/directory/currency',
        'mage/translate',
        'model/checkout/integration/pdf-invoice-plus',
        'lib/jquery/jquery-barcode'
    ],
    function ($, ko, Component, orderTotal, Helper, DateHelper, AddNoti, currency, $t, PdfInvoicePlusModel) {
        "use strict";

        return Component.extend({
            containerId: 'container-print-order',
            codeCheck: ['rewardpoints_earn', 'rewardpoints_spent', 'rewardpoints_discount', 'gift_voucher_discount', 'shipping_amount', 'discount_amount', 'tax_amount', 'total_due'],
            totalsCode: ko.observableArray(),
            configs: ko.observableArray(),
            baseRate:  ko.observableArray(),
            currentRate:  ko.observableArray(),
            customerAdditionalInfomation: ko.observableArray(),
            currentCurrencyCode: window.webposConfig.currentCurrencyCode,
            baseCurrencyCode: window.webposConfig.baseCurrencyCode,
            defaults: {
                template: 'ui/order/print',
            },

            initialize: function () {
                this._super();
                var self = this;
                self.initDefaultData();
                ko.pureComputed(function() {
                    return self.orderData();
                }).subscribe(function() {
                    if(self.orderData()){
                        self.initDefaultData();
                        $("#webpos_sales_order_receipt_barcode").html("").barcode(self.getOrderData('increment_id'), "code128", {
                            output:"css",
                            bgColor: "#FFFFFF",
                            color: "#000000",
                            barWidth: 1,
                            barHeight: 20
                        });
                    }
                });

            },

            initDefaultData: function(){
                var self = this;
                var discountLabel = (self.getOrderData().discount_description)?('Discount' +
                '(' + self.orderData().discount_description + ')'):'Discount';
                var totalsCode = [
                    {code:'subtotal',title:$t('Subtotal'), required:true, sortOrder: 1, isPrice: true},
                    {code:'shipping_amount',title:$t('Shipping'), required:true, sortOrder: 10, isPrice: true},
                    {code:'tax_amount',title:$t('Tax'), required:true,  sortOrder: 20, isPrice: true},
                    {code:'discount_amount',title:discountLabel, required:false,  sortOrder: 30, isPrice: true},
                    {code:'grand_total',title:$t('Grand Total'), required:true,  sortOrder: 40, isPrice: true},
                    {code:'total_paid',title:$t('Total Paid'), required:true,  sortOrder: 50, isPrice: true},
                    {code:'total_due',title:$t('Total Due'), required:true,  sortOrder: 60, isPrice: true}
                ];

                if(self.hasRefundAmount()){
                    totalsCode.push({code:'total_refunded',title:$t('Total Refunded'), required:true,  sortOrder: 60, isPrice: true});
                }

                var eventData = {
                    totals:totalsCode
                };
                Helper.dispatchEvent('prepare_receipt_totals', eventData);
                totalsCode.sort(function(a, b) {
                    if(!a.sortOrder){
                        a.sortOrder = 2;
                    }
                    if(!b.sortOrder){
                        b.sortOrder = 2;
                    }
                    return parseFloat(a.sortOrder) - parseFloat(b.sortOrder);
                });
                this.totalsCode(totalsCode);

                var configs = [
                    {code:'auto_print',value:window.webposConfig["webpos/receipt/auto_print"]},
                    {code:'font_type',value:window.webposConfig["webpos/receipt/font_type"]},
                    {code:'footer_text',value:window.webposConfig["webpos/receipt/footer_text"]},
                    {code:'header_text',value:window.webposConfig["webpos/receipt/header_text"]},
                    {code:'show_cashier_name',value:window.webposConfig["webpos/receipt/show_cashier_name"]},
                    {code:'show_comment',value:window.webposConfig["webpos/receipt/show_comment"]},
                    {code:'show_barcode',value:window.webposConfig["webpos/receipt/show_barcode"]},
                    {code:'show_receipt_logo',value:window.webposConfig["webpos/receipt/show_receipt_logo"]},
                    {code:'logo',value:window.webposConfig["webpos/general/webpos_logo"]}
                ];
                this.configs(configs);
            },
            preparePrintData: function (data) {
                if(this.codeCheck.indexOf(data.code) != -1 && parseFloat(data.amount) == 0)
                    return false;
                if(data.label == 'Gift Card'){
                    data.value = '-' + data.value;
                }
                return true;
            },
            hasRefundAmount: function(){
                return (this.getOrderData('total_refunded')>0) ? true : false;
            },
            hasGiftCard: function(label){
                if(label == 'Gift Card'){
                    return ' ('+ this.getOrderData('gift_codes') +')';
                }
                return '';
            },
            formatPrice: function(string){
                var self = this;
                var rates = currency().getCollection().load();
                var from =  self.getOrderData().order_currency_code;
                var to =  self.getOrderData().base_currency_code;

                var currentCurrency = window.webposConfig.currentCurrencyCode;
                rates.done(function (data) {
                    var baseRate  = '' ;
                    var baseToCurrent  = '' ;
                    if (data.items.length > 0) {
                        $.each(data.items, function (index, value) {
                            if (value.code == from) {
                                baseRate = (parseFloat(value.currency_rate));
                                self.baseRate(baseRate);
                            }
                            if (value.code == currentCurrency) {
                                baseToCurrent = (parseFloat(value.currency_rate));
                                self.currentRate(baseToCurrent);
                            }
                        });
                    }
                });
                if (self.baseRate() != '' && self.currentRate() != '') {
                    return Helper.formatPrice(string/self.baseRate()*self.currentRate());
                } else {
                    return Helper.formatPrice(string);
                }

            },

            currencyConvert: function(string,from,to){
                return Helper.convertPrice(string,from,to);
            },

            getConfigData: function(code){
                if(this.configs()){
                    var config = ko.utils.arrayFirst(this.configs(), function(config){
                        return (config && config.code == code);
                    });
                    if(config){
                        return config.value;
                    }
                }
                return "";
            },

            getOrderData: function (key) {
                var self = this;
                var data = false;
                if(self.orderData()){
                    data = self.orderData();
                    if(key){
                        if(typeof data[key] != "undefined"){
                            data = data[key];
                        }else{
                            data = ""
                        }
                    }
                }
                return data;
            },

            isShowBarcode: function(){
                return (this.getConfigData('show_barcode') == 1)?true:false;
            },
            isAutoPrint: function(){
                return (this.getConfigData('auto_print') == 1)?true:false;
            },

            getFont: function(){
                return this.getConfigData('font_type');
            },
            getFooterHtml: function(){
                return this.getConfigData('footer_text');
            },
            getHeaderHtml: function(){
                return this.getConfigData('header_text');
            },
            hasHeaderHtml: function(){
                return (this.getConfigData('header_text'))?true:false;
            },
            isShowCashierName: function(){
                return (this.getConfigData('show_cashier_name')== 1)?true:false;
            },
            isShowComment: function(){
                return (this.getConfigData('show_comment')== 1 && this.getComment())?true:false;
            },
            isShowLogo: function(){
                return (this.getConfigData('show_receipt_logo')== 1 && this.getLogoPath())?true:false;
            },
            getLogoPath: function(){
                return this.getConfigData('logo');
            },
            getIncrementId: function(){
                return "#"+this.getOrderData('increment_id');
            },
            getCreatedDate: function(){
                return DateHelper.getDate(this.getOrderData('created_at'));
            },
            getCashierName: function(){
                return this.getOrderData('webpos_staff_name');
            },
            getCreatedTime: function(){
                return DateHelper.getTime(this.getOrderData('created_at'));
            },
            getComment: function(){
                return this.getOrderData('customer_note');
            },
            getShipping: function(){
                return this.getOrderData('shipping_description');
            },
            getDeliveryDate: function(){
                var deliveryTime = this.getOrderData('webpos_delivery_date');
                return (deliveryTime)?DateHelper.getFullDatetime(deliveryTime):'';
            },
            hasDeliveryDate: function(){
                var deliveryTime = this.getOrderData('webpos_delivery_date');
                return (deliveryTime)?true:false;
            },
            hasShipping: function(){
                return (this.getOrderData('shipping_amount')>0)?true:false;
            },
            getCustomerName: function(){
                return this.getOrderData('customer_firstname')+" "+this.getOrderData('customer_lastname');
            },
            hasCustomerName: function(){
                return (this.getOrderData('customer_firstname') || this.getOrderData('customer_lastname'))?true:false;
            },
            getWebposChange: function(){
                return this.getOrderData('webpos_change')+" "+this.getOrderData('webpos_change');
            },
            hasWebposChange: function(){
                return (this.getOrderData('webpos_change')>0)?true:false;
            },
            getPayment: function(){
                var payments = [];
                if(this.getOrderData('webpos_order_payments') && this.getOrderData('webpos_order_payments').length > 0){
                    ko.utils.arrayForEach(this.getOrderData('webpos_order_payments'), function(payment) {
                        if(payment.payment_amount > 0){
                            var data = {
                                method_title:payment.method_title,
                                payment_amount:payment.payment_amount
                            };
                            if(payment.reference_number){
                                data.method_title = payment.method_title + ' (' + payment.reference_number +')';
                            }
                            payments.push(data);
                        }
                    });
                }
                return payments;
            },
            hasPayment: function(){
                return (this.getPayment() && this.getPayment().length >0)?true:false;
            },
            getItems: function(){
                return this.getOrderData('items');
            },
            getOrderTotals: function(){
                var self = this;
                var totals = [];

                if(self.totalsCode() && self.totalsCode().length > 0){
                    ko.utils.arrayForEach(self.totalsCode(), function(data) {
                        var amount = self.getOrderData(data.code);
                        if(data.code == 'gift_voucher_discount' && Helper.isGiftCardEnable()){
                            amount = -amount;
                        }
                        if(amount || (data.required && data.required == true)){
                            var value = (data.isPrice == false)?(amount +' '+data.valueLabel):(self.formatPrice(amount));
                            var total = {
                                'label':data.title,
                                'value':value,
                                'code':data.code,
                                'amount':amount
                            };
                            totals.push(total);
                        }
                    });
                }
                return totals;
            },
            getCustomerAdditionalInfo: function(){
                var self = this;
                if(self.customerAdditionalInfomation() && self.customerAdditionalInfomation().length > 0){
                    return self.customerAdditionalInfomation();
                }
                return [];
            },
            printReceipt: function(){
                var self = this;
                self.initDefaultData();
                if(Helper.isPdfInvoicePlusEnable() && Helper.getPdfInvoiceTemplate()){
                    var orderId = self.getOrderData('entity_id');
                    PdfInvoicePlusModel.startPrint(orderId);
                    return true;
                }
                var print_window = window.open('', 'print_offline', 'status=1,width=500,height=700');
                var html = self.toHtml();
                if(print_window){
                    self.printWindow(print_window);
                    print_window.document.open();
                    print_window.document.write(html);
                    print_window.print();
                }else{
                    AddNoti(self.__("Your browser has blocked the automatic popup, please change your browser setting or print the receipt manually"), true, "warning", self.__('Message'));
                }
            },
            getPaynlInstoreReceipt: function(){
                var self = this;
                var receipt = '';
                if(self.getOrderData('paynl_instore_receipt')){
                    receipt = self.getOrderData('paynl_instore_receipt');
                    receipt = receipt.split(" ").join("&nbsp;");
                    receipt = receipt.split("\n").join("<br />");
                }
                return receipt;
            }
        });
    }
);