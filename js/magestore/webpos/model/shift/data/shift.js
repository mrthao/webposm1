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
        'ko',
        'jquery',
        'helper/general',
        'helper/datetime',
        'helper/staff',
        'helper/price',
        'model/shift/tills',
        'model/shift/data/transaction',
        'model/resource-model/magento-rest/shift/shift',
        'model/appConfig',
        'eventManager'
    ],
    function (ko, $, Helper, HelperDatetime, Staff, PriceHelper, Tills, TransactionRepository, ShiftResource, AppConfig, Event) {
        "use strict";

        var ShiftRepository = {
            openAt: ko.observable(),
            closeAt: ko.observable(''),
            openingAmount: ko.observable(0),
            closedAmount: ko.observable(''),
            cashLeftAmount: ko.observable(''),
            transactions: TransactionRepository.transactions,
            salesByPayments: ko.observableArray([]),
            salesSummary: ko.observable({
                base_grand_total:0,
                base_total_refunded:0,
                base_discount_amount:0,
                grand_total:0,
                total_refunded:0,
                discount_amount:0
            }),
            cashDrawerData: ko.observable({balance:0,base_balance:0}),
            initialize: function(){
                var self = this;
                self.initComputed();
                return self;
            },
            initData: function(){
                var self = this;
                var deferred = $.Deferred();
                var currentCashDrawer = Tills.currentTill();
                if(currentCashDrawer.id != 0){
                    TransactionRepository.gets(currentCashDrawer.id);
                    var params = {
                        till_id: (currentCashDrawer.id)?currentCashDrawer.id:0
                    };
                    ShiftResource().setPush(true).setLog(false).getShiftData(params,deferred);
                    deferred.done(function(response){
                        if(response.status && response.data){
                            self.salesSummary(self.prepareSummary(response.data.sales_summary));
                            self.salesByPayments(response.data.sales_by_payments);
                            self.cashDrawerData({balance:Helper.convertPrice(response.data.base_balance),base_balance:response.data.base_balance});
                            self.openingAmount(parseFloat(Helper.convertPrice(response.data.base_opening_amount)));
                            self.openAt(response.data.open_at);
                        }
                    });
                    Event.dispatch(AppConfig.EVENT.OPEN_SHIFT_AFTER, '');
                }
            },
            initComputed: function(){
                var self = this;
                self.cashAddedManual = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual  == '1' && transaction.amount > 0 && transaction.is_opening  == '0'){
                            total += self.getTransactionAmount(transaction);
                        }
                    })
                    return total;
                });
                self.cashRemovedManual = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual  == '1' && transaction.amount < 0){
                            total += self.getTransactionAmount(transaction);
                        }
                    })
                    return total;
                });
                self.cashByOrders = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual  == '0'){
                            total += self.getTransactionAmount(transaction);
                        }
                    })
                    return total;
                });
                self.baseCashAddedManual = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual  == '1' && transaction.base_amount > 0 && transaction.is_opening  == '0'){
                            total += self.getTransactionBaseAmount(transaction);
                        }
                    })
                    return total;
                });
                self.baseCashRemovedManual = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual  == '1' && transaction.base_amount < 0){
                            total += self.getTransactionBaseAmount(transaction);
                        }
                    })
                    return total;
                });
                self.baseCashByOrders = ko.pureComputed(function(){
                    var total = 0;
                    ko.utils.arrayForEach(self.transactions(), function(transaction){
                        if(transaction.is_manual == '0'){
                            total += self.getTransactionBaseAmount(transaction);
                        }
                    })
                    return total;
                });
                self.cashBalance = ko.pureComputed(function(){
                    var balance = self.openingAmount();
                    balance += self.cashAddedManual();
                    balance += self.cashRemovedManual();
                    balance += self.cashByOrders();
                    return balance;
                });
            },
            getDataForZreport: function(){
                var self = this;
                var data = {
                    sale_by_payments: self.salesByPayments(),
                    sales_summary: self.salesSummary(),
                    opened_at: self.openAt(),
                    closed_at: self.closeAt(),
                    opening_amount: self.openingAmount(),
                    closed_amount: self.closedAmount(),
                    cash_left: self.cashLeftAmount(),
                    cash_added: self.cashAddedManual(),
                    cash_removed: self.cashRemovedManual(),
                    cash_sale: self.cashByOrders(),
                    cash_balance: self.cashBalance()
                };

                return data;
            },
            closeStore: function(closeData){
                if(closeData){
                    var self = this;
                    self.closeAt(HelperDatetime.getBaseSqlDatetime());
                    if(closeData.closed_amount){
                        self.closedAmount(closeData.closed_amount)
                    }
                    if(closeData.cash_left){
                        self.cashLeftAmount(closeData.cash_left)
                    }
                    var data = {
                        till_id: Tills.currentTill().id,
                        staff_id: Staff.getStaffId(),
                        opened_at: self.openAt(),
                        closed_at: self.closeAt(),
                        opening_amount: self.openingAmount(),
                        base_opening_amount: PriceHelper.toBasePrice(self.openingAmount()),
                        closed_amount: self.closedAmount(),
                        base_closed_amount: PriceHelper.toBasePrice(self.closedAmount()),
                        cash_left: self.cashLeftAmount(),
                        base_cash_left: PriceHelper.toBasePrice(self.cashLeftAmount()),
                        cash_added: self.cashAddedManual(),
                        base_cash_added: self.baseCashAddedManual(),
                        cash_removed: self.cashRemovedManual(),
                        base_cash_removed: self.baseCashRemovedManual(),
                        cash_sale: self.cashByOrders(),
                        base_cash_sale: self.baseCashByOrders(),
                        report_currency_code: PriceHelper.currentCurrencyCode,
                        base_currency_code: PriceHelper.baseCurrencyCode,
                        sale_by_payments: JSON.stringify(self.salesByPayments()),
                        sales_summary: JSON.stringify(self.salesSummary()),
                        note: closeData.closed_note
                    };
                    var deferred = $.Deferred();
                    var params = {
                        data: data
                    };
                    ShiftResource().setPush(true).setLog(false).closeStore(params,deferred);
                    deferred.done(function(response){
                        if(response.status && response.data){
                            Event.dispatch(AppConfig.EVENT.CLOSE_SHIFT_AFTER, '');
                            Event.dispatch(AppConfig.EVENT.PRINT_ZREPORT, '');
                        }
                    });
                }
            },
            /**
             * Convert price
             * @param summary
             * @returns {*}
             */
            prepareSummary: function(summary){
                summary.grand_total = Helper.convertPrice(summary.base_grand_total);
                summary.total_refunded = Helper.convertPrice(summary.base_total_refunded);
                summary.discount_amount = Helper.convertPrice(summary.base_discount_amount);
                return summary;
            },
            /**
             * Reset closed data
             */
            resetCloseData: function(){
                var self = this;
                self.closeAt('');
                self.closedAmount('');
                self.cashLeftAmount('');
            },

            /**
             * @param transaction
             * @returns {*}
             */
            getTransactionAmount: function(transaction){
                if(transaction.transaction_currency_code == PriceHelper.currentCurrencyCode){
                    return parseFloat(transaction.amount);
                }
                if(transaction.base_currency_code == PriceHelper.currentCurrencyCode){
                    return parseFloat(transaction.base_amount);
                }

                if(transaction.base_currency_code == PriceHelper.baseCurrencyCode){
                    return parseFloat(Helper.convertPrice(transaction.base_amount));
                }
                return parseFloat(Helper.convertPrice(transaction.amount, transaction.transaction_currency_code, PriceHelper.currentCurrencyCode));
            },
            /**
             * @param transaction
             * @returns {*}
             */
            getTransactionBaseAmount: function(transaction){
                if(transaction.base_currency_code == PriceHelper.baseCurrencyCode){
                    return parseFloat(transaction.base_amount);
                }
                if(transaction.base_currency_code == PriceHelper.currentCurrencyCode){
                    var baseAmount = parseFloat(transaction.base_amount);
                    return parseFloat(PriceHelper.toBasePrice(baseAmount));
                }
                if(transaction.transaction_currency_code == PriceHelper.baseCurrencyCode){
                    return parseFloat(transaction.amount);
                }
                return parseFloat(Helper.convertPrice(transaction.base_amount, transaction.base_currency_code, PriceHelper.baseCurrencyCode));
            }
        };
        return ShiftRepository.initialize();
    }
);