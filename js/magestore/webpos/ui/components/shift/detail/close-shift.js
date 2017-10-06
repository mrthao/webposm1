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
        'helper/staff',
        'action/notification/add-notification',
        'eventManager',
        'model/shift/data/shift',
        'model/shift/data/denomination'
    ],
    function ($, ko, Component, priceHelper, datetimeHelper, Staff, notification, Event, ShiftRepository, Denomination) {
        "use strict";

        return Component.extend({
            denominations: ko.observableArray([]),
            closed_amount: ko.observable(''),
            closed_note: ko.observable(''),
            cash_left: ko.observable(''),
            closedAmountFormatted: ko.observable(''),
            cashLeftFormatted: ko.observable(''),
            keypressWaiting: '',
            cashLeftErrorMessage: ko.observable(''),
            staffName: Staff.getStaffName(),
            defaults: {
                template: 'ui/shift/shift/close-shift',
            },

            initialize: function () {
                this._super();

                //recalculate closedAmountFormatted when closed_amount changed.
                this.closedAmountFormatted = ko.pureComputed(function () {
                    this.validateInputAmount();
                    return priceHelper.formatPrice(this.closed_amount());
                }, this);

                //recalculate cashLeftFormatted when cash_left changed.
                this.cashLeftFormatted = ko.pureComputed(function () {
                    this.validateInputAmount();
                    return priceHelper.formatPrice(this.cash_left());
                }, this);

                this.balance = ko.pureComputed(function () {
                    return priceHelper.formatPrice(ShiftRepository.cashBalance());
                });

                this.defaultOpeningCash = window.webposConfig['webpos/report/default_transfer_money'];
                this.cashCountingDenomination = window.webposConfig['webpos/report/denomination'];
                this.cash_left(this.defaultOpeningCash);
            },

            /**
             * check if cash_left is less than closed amount or not
             * @returns {boolean}
             */
            validateInputAmount: function () {
                if (this.cash_left() > this.closed_amount()) {
                    this.cashLeftErrorMessage("Cash left must be less than the Closed amount");
                    return false;
                }
                else {
                    this.cashLeftErrorMessage("");
                    return true;
                }
            },

            /* update value of the estimated cash in the cash drawer*/
            initData: function () {
                this.balance(this.shiftData().balance);
                this.balance(priceHelper.formatPrice(this.balance()));
            },

            //get data from the form and call to CashTransaction model then save to database
            closeShift: function () {
                var self = this;
                if (!self.validateInputAmount()) {
                    return;
                }
                ShiftRepository.closeStore({
                    closed_amount: self.closed_amount(),
                    closed_note: self.closed_note(),
                    cash_left: self.cash_left()
                });
                self.closeForm();
            },

            /**
             * do some additional task when everything is completed.
             */
            closeCompleted: function () {
                this.cash_left(this.defaultOpeningCash);
                this.closed_amount('');
                this.closed_note('');
                this.clearDenominationQty();
            },
            closeForm: function () {
                $(".popup-for-right").hide();
                $(".popup-for-right").removeClass('fade-in');
                $(".wrap-backover").hide();
                $('.notification-bell').show();
                $('#c-button--push-left').show();
                this.closeCompleted();
            },

            cashLeftChange: function (data, event) {
                this.cash_left(priceHelper.toNumber(event.target.value));

            },

            closedAmountChange: function (data, event) {
                this.closed_amount(priceHelper.toNumber(event.target.value));
            },

            formatTransactionPrice: function(amount, transaction){
                return priceHelper.convertAndFormat(amount, transaction.transaction_currency_code);
            },

            convertAndFormatPrice: function(amount){
                return priceHelper.convertAndFormat(amount);
            },

            getDenominations: function(){
                var self = this;
                var denominations = [];
                var configDenominations = self.cashCountingDenomination;
                if(configDenominations){
                    var arrayVal = configDenominations.split(',');
                    $.each(arrayVal, function(index, value){
                        var valueArr = value.split(':');
                        var denomination = new Denomination();
                        denomination.name = valueArr[0];
                        denomination.value = parseFloat(valueArr[1]);
                        denominations.push(denomination);
                        self.denominations.push(denomination);
                    });
                }
                return denominations;
            },

            denoQtyChange: function(data, event){
                var self = this;
                var qty = parseFloat(event.target.value);
                qty = (qty > 0)?qty:0;
                event.target.value = qty;
                var deno = ko.utils.arrayFirst(self.denominations(), function(denomination){
                    return (denomination == data);
                });
                if(deno){
                    deno.qty = qty;
                    deno.qtyChanged(!deno.qtyChanged());
                }
                self.calculateCashTotals();
            },

            calculateCashTotals: function(){
                var self = this;
                var totals = 0;
                ko.utils.arrayForEach(self.denominations(), function(denomination){
                    totals += denomination.total();
                });
                self.closed_amount(totals);
            },

            clearDenominationQty: function(){
                var self = this;
                ko.utils.arrayForEach(self.denominations(), function(denomination){
                    denomination.qty = 0;
                    denomination.qtyChanged(!denomination.qtyChanged());
                });
                $('#count_cash_area .count_value').val(0);
                self.calculateCashTotals();
            }
        });
    }
);