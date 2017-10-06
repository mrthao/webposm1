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
        'posComponent',
        'helper/price',
        'helper/datetime',
        'model/shift/data/shift',
        'eventManager',
        'model/appConfig'
    ],
    function ($, ko, Component,  priceHelper, datetimeHelper, ShiftModel, Event, AppConfig) {
        "use strict";

        return Component.extend({
            shiftData:  ko.observable({}),
            openedAtFormatted: ko.observable(),
            closedAtFormatted: ko.observable(),
            floatAmountFormatted: ko.observable(),
            cashLeftFormatted: ko.observable(),
            closedAmountFormatted: ko.observable(),
            totalSalesFormatted: ko.observable(),
            refundFormatted: ko.observable(),
            discountFormatted: ko.observable(),
            cashAddedFormatted: ko.observable(),
            cashRemovedFormatted: ko.observable(),
            cashSaleFormatted: ko.observable(),
            cashBalanceFormatted: ko.observable(),
            staffName: ko.observable(window.webposConfig.staffName),

            defaults: {
                template: 'ui/shift/sales-summary/zreport',
            },
            initialize: function(){
                this._super();
                var self = this;
                Event.observer(AppConfig.EVENT.INIT_SHIFT_REPORT_DATA, function(){
                    self.initData();
                });
            },
            initData: function(){
                var self = this;
                var data = ShiftModel.getDataForZreport();
                self.shiftData(data);
                if(data.opened_at){
                    self.openedAtFormatted(datetimeHelper.getFullDatetime(data.opened_at));
                }
                self.closedAtFormatted(datetimeHelper.getFullDatetime(data.closed_at));
                if(typeof data.opening_amount != 'undefined') {
                    self.floatAmountFormatted(priceHelper.formatPrice(data.opening_amount));
                }
                if(data.closed_amount != ''){
                    self.closedAmountFormatted(priceHelper.formatPrice(data.closed_amount));
                }else{
                    self.closedAmountFormatted('');
                }
                if(data.cash_left != ''){
                    self.cashLeftFormatted(priceHelper.formatPrice(data.cash_left));
                }else{
                    self.cashLeftFormatted('');
                }

                if(typeof data.cash_added != 'undefined') {
                    self.cashAddedFormatted(priceHelper.formatPrice(data.cash_added));
                }
                if(typeof data.cash_removed != 'undefined') {
                    self.cashRemovedFormatted(priceHelper.formatPrice(data.cash_removed));
                }
                if(typeof data.cash_sale != 'undefined') {
                    self.cashSaleFormatted(priceHelper.formatPrice(data.cash_sale));
                }
                if(typeof data.cash_balance != 'undefined') {
                    self.cashBalanceFormatted(priceHelper.formatPrice(data.cash_balance));
                }

                var sales_summary = data.sales_summary;
                if(typeof sales_summary.grand_total != 'undefined') {
                    self.totalSalesFormatted(priceHelper.formatPrice(sales_summary.grand_total));
                }
                if(typeof sales_summary.total_refunded != 'undefined') {
                    self.refundFormatted(priceHelper.formatPrice(sales_summary.total_refunded));
                }
                if(typeof sales_summary.discount_amount != 'undefined') {
                    self.discountFormatted(priceHelper.formatPrice(sales_summary.discount_amount));
                }
            },

            getFont: function(){
                return window.webposConfig["webpos/receipt/font_type"];
            },
            printReport: function () {
                var html = $('#zreport-print-content').html();
                var print_window = window.open('', 'print_offline', 'status=1,width=700,height=700');
                print_window.document.write(html);
                print_window.print();
            },
            
            formatPrice: function (value) {
                return priceHelper.formatPrice(value);
            }

        });
    }
);