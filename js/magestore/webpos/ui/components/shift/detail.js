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
        'model/shift/tills',
        'eventManager',
        'model/appConfig'
    ],
    function ($, ko, Component, Tills, Event, AppConfig) {
        "use strict";

        return Component.extend({
            shiftData: ko.observable({}),
            isClosedShift: ko.observable(false),
            noSalesSummary: ko.observable('main-content'),

            defaults: {
                template: 'ui/shift/shift/shift-detail',
            },

            initialize: function () {
                this._super();
                var self = this;
                self.cashDrawerName = ko.pureComputed(function(){
                    return Tills.currentTill().title;
                })
                Event.observer(AppConfig.EVENT.CLOSE_SHIFT_AFTER, function () {
                    self.isClosedShift(true);
                });
                Event.observer(AppConfig.EVENT.OPEN_SHIFT_AFTER, function () {
                    self.isClosedShift(false);
                });
            },

            setShiftData: function(data){
                this.shiftData(data);
                if (data.status == 1){
                    this.isClosedShift(true);
                }
                else {
                    this.isClosedShift(false);
                }
                if(data.sale_summary.length == 0){
                    this.noSalesSummary("no-sales-summary main-content");
                }
                else {
                    this.noSalesSummary("main-content");
                }
            },
            
            afterClosedShift: function () {
                this.isClosedShift(true);
            },

            afterRenderCashAdjustmentButton: function () {
                $('.footer-shift .btn-make-adjustment').click(function (event) {
                    //var ptop = (event.pageY/2) - 785;
                    var ptop = 150;
                    $("#popup-make-adjustment").addClass('fade-in');
                    $("#popup-make-adjustment").css({top: ptop + 'px'}).fadeIn(350);
                    $('.notification-bell').hide();
                    $(".wrap-backover").show();
                });

                $('.wrap-backover').click(function () {
                    $(".popup-for-right").hide();
                    $(".popup-for-right").removeClass('fade-in');
                    $('.notification-bell').show();
                    $(".wrap-backover").hide();
                });
            },

            afterRenderCloseButton: function () {
                $('.footer-shift .btn-close-shift').click(function (event) {
                    //var ptop = (event.pageY/2) - 185;
                    var ptop = 150;
                    $("#popup-close-shift").addClass('fade-in');
                    $("#popup-close-shift").css({ top: ptop + 'px'}).fadeIn(350);
                    $(".wrap-backover").show();
                    $('.notification-bell').hide();
                    $('#c-button--push-left').hide();
                });

                $('.wrap-backover').click(function () {
                    $(".popup-for-right").hide();
                    $(".popup-for-right").removeClass('fade-in');
                    $(".wrap-backover").hide();
                    $('.notification-bell').show();
                    $('#c-button--push-left').show();
                });
            },

            afterRenderZReportButton: function () {
                Event.observer(AppConfig.EVENT.PRINT_ZREPORT, function () {
                    $('.footer-shift .btn-print').click();
                });

                $('.footer-shift .btn-print').click(function () {
                    Event.dispatch(AppConfig.EVENT.INIT_SHIFT_REPORT_DATA, '');
                    var ptop = 10;
                    $("#print-shift-popup").addClass('fade-in');
                    $("#print-shift-popup").css({ top: ptop + 'px'});
                    $(".wrap-backover").show();
                    $('.notification-bell').hide();
                    $('#c-button--push-left').hide();
                });

                $('.wrap-backover').click(function () {
                    $(".popup-for-right").hide();
                    $(".popup-for-right").removeClass('fade-in');
                    $(".wrap-backover").hide();
                    $('.notification-bell').show();
                    $('#c-button--push-left').show();   
                });
            }

        });
    }
);
