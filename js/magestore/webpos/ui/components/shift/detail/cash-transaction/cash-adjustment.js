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
        'helper/staff',
        'model/shift/data/transaction',
        'model/shift/data/shift',
        'model/shift/tills',
        'model/appConfig'
    ],
    function ($, ko, Component, PriceHelper, Staff, TransactionRepository, ShiftModel, Tills, AppConfig) {
        "use strict";

        return Component.extend({
            shiftData: ko.observable({}),
            value: ko.observable(''),
            note: ko.observable(''),
            add_cash_class: ko.observable('cash_adjustment_active'),
            remove_cash_class: ko.observable('cash_adjustment_inactive'),
            valueFormatted: ko.observable(''),
            balance: ko.observable(''),
            balanceFormatted: ko.observable(''),
            valueErrorMessage: ko.observable(''),
            type: ko.observable('add'),
            staffId: ko.observable(window.webposConfig.staffId),
            staffName: ko.observable(window.webposConfig.staffName),

            defaults: {
                template: 'ui/shift/cash-transaction/cash-adjustment',
            },

            initialize: function () {
                this._super();
                this.valueFormatted = ko.pureComputed(function () {
                    return PriceHelper.formatPrice(this.value());
                }, this)
                var self = this;
                ShiftModel.cashDrawerData.subscribe(function(data){
                    self.balance(data.balance);
                    self.balanceFormatted(PriceHelper.formatPrice(data.balance));
                });
                self.observerEvent(AppConfig.EVENT.REFUND_CASH_AFTER, function(event, data){
                    data.till_id = Tills.currentTill().id;
                    data.staff_id = Staff.getStaffId();
                    TransactionRepository.save(data);
                });
            },

            //set all cash transaction data of the selected shift to Items
            //each transaction is an Item
            setData: function (data) {
                this.setItems(data);
            },

            //set all information of the selected shift to ShiftData
            //call this function from shift-listing
            setShiftData: function (data) {
                this.shiftData(data);
                this.balance(data.balance);
                this.balanceFormatted(PriceHelper.formatPrice(data.balance));
            },

            //change the value of type to "add"
            addCash: function () {
                this.type('add');
                this.add_cash_class('cash_adjustment_active');
                this.remove_cash_class('cash_adjustment_inactive');
                this.clearInput();
            },

            //change the value of type to "remove"
            removeCash: function () {
                this.type('remove');
                this.add_cash_class('cash_adjustment_inactive');
                this.remove_cash_class('cash_adjustment_active');
                this.clearInput();
            },

            //get data from the form and call to CashTransaction model then save to database
            createCashAdjustment: function () {
                if (!this.validateInputAmount()) {
                    return;
                }
                this.saveTransaction();
                this.closeForm();
                this.clearInput();
            },

            saveTransaction: function () {
                var self = this;
                var finalValue = self.parseFloatValue(this.value());
                finalValue = (self.type() == 'remove')?-finalValue:finalValue;
                var data = {
                    'till_id': Tills.currentTill().id,
                    'staff_id': Staff.getStaffId(),
                    'order_increment_id':0,
                    'amount': finalValue,
                    'base_amount': PriceHelper.toBasePrice(finalValue),
                    'transaction_currency_code':PriceHelper.currentCurrencyCode,
                    'base_currency_code':PriceHelper.baseCurrencyCode,
                    'note': self.note()
                };
                TransactionRepository.save(data).done(function(){
                    ShiftModel.initData();
                });
            },
            closeForm: function () {
                $(".popup-for-right").hide();
                $(".popup-for-right").removeClass('fade-in');
                $(".wrap-backover").hide();
                $('.notification-bell').show();
                $('#c-button--push-left').show();
            },

            clearInput: function () {
                //clear input value
                this.value('');
                this.note('');
                this.valueErrorMessage("");
            },

            valueChange: function (data, event) {
                this.value(PriceHelper.toNumber(event.target.value));
                this.validateInputAmount();
            },

            /**
             * check if remove value is less than current balance or not
             * @returns {boolean}
             */
            validateInputAmount: function () {
                if ((this.value() > this.balance()) && (this.type() == 'remove')) {
                    this.valueErrorMessage("Remove amount must be less than the balance!");
                    return false;
                }

                if (this.value() <= 0) {
                    this.valueErrorMessage("Amount must be greater than 0!");
                    return false;
                }

                this.valueErrorMessage("");
                return true;
            },

            //parseInt a field from SQL.
            parseFloatValue: function (value) {
                if (!value) {
                    return 0;
                }
                value = parseFloat(value);
                return value;
            }
        });
    }
);
