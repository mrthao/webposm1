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
        'model/shift/tills',
        'model/appConfig',
        'model/shift/data/shift'
    ],
    function ($, ko, Component, priceHelper, datetimeHelper, Tills, AppConfig, ShiftModel) {
        "use strict";

        return Component.extend({
            items: ShiftModel.transactions,
            staffId: ko.observable(window.webposConfig.staffId),
            isEnable: Tills.isEnable,
            canSwitchCashDrawer: ko.pureComputed(function(){
                return Tills.tills().length > 1;
            }),

            defaults: {
                template: 'ui/shift/shift/shift-listing',
            },
            initialize: function () {
                this._super();
                this.initEvent();
                this.isShowHeader = true;
            },

            getDateOnly: function (dateString) {
                var datetime = this.reFormatDateString(dateString);

                return datetimeHelper.getWeekDay(dateString) + " " + datetime.getDate() + " " + datetimeHelper.getMonthShortText(dateString);
            },

            getTimeOnly: function (datetime) {
                return datetimeHelper.getTimeOfDay(datetime);
            },


            reFormatDateString: function (dateString) {
                return new Date(dateString);
            },

            afterRenderOpenButton: function () {
                $('#shift_container .icon-add .icon-iconPOS-add').click(function () {
                    $("#popup-open-shift").addClass('fade-in');
                    $(".wrap-backover").show();
                });
            },

            initEvent: function () {
                var self = this;
                self.observerEvent('cash_drawer_show_container_after', function (event, eventData) {
                    ShiftModel.initData();
                    if(ShiftModel.closedAmount()){
                        ShiftModel.resetCloseData();
                    }
                });
                self.observerEvent(AppConfig.EVENT.SELECT_CASH_DRAWER_AFTER, function (event, eventData) {
                    ShiftModel.initData();
                    if(ShiftModel.closedAmount()){
                        ShiftModel.resetCloseData();
                    }
                });
            },

            toNumber: function (amount) {
                return priceHelper.toNumber(amount);
            },

            switchCashDrawer: function(){
                var self = this;
                self.dispatchEvent(AppConfig.EVENT.SHOW_POPUP_SELECT_CASH_DRAWER, '');
                var commentPopup =  $('#select_cash_drawer_popup');
                if(commentPopup.length > 0) {
                    commentPopup.posOverlay({
                        onClose: function () {
                            $('.notification-bell').show();
                        }
                    });
                }
            },

            formatTransactionPrice: function(amount, transaction){
                if(transaction.transaction_currency_code == priceHelper.currentCurrencyCode){
                    return priceHelper.formatPrice(amount);
                }
                if(transaction.base_currency_code == priceHelper.currentCurrencyCode){
                    return priceHelper.formatPrice(transaction.base_amount);
                }
                return priceHelper.convertAndFormat(transaction.base_amount, transaction.base_currency_code, priceHelper.currentCurrencyCode);
            }
        });
    }
);