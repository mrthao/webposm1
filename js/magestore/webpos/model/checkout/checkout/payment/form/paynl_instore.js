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
        'model/checkout/checkout',
        'helper/general'
    ],
    function ($, ko, CheckoutModel, Helper) {
        "use strict";
         var PaynlInstoreModel = {
             CODE: 'pay_payment_instore',
             SELECTED_TERMINAL_PATH: 'pay_payment_instore.selected_terminal',
             selectedTerminal: ko.observable(),
             terminals: ko.observableArray([]),
             initialize: function () {
                 var self = this;
                 self.initEvents();
                 return self;
             },
             initEvents: function(){
                 var self = this;
                 Helper.observerEvent('webpos_place_order_before', function (event, data) {
                     self.placeOrderBefore(data);
                 });
                 Helper.observerEvent('webpos_place_order_online_before', function (event, data) {
                     self.placeOrderOnlineBefore(data);
                 });
                 self.selectedTerminal.subscribe(function(terminalId){
                     Helper.saveLocalConfig(self.SELECTED_TERMINAL_PATH, terminalId);
                 });
                 return self;
             },
             initData: function(form_data){
                 var self = this;
                 var form_data = JSON.parse(form_data);
                 if(form_data.terminals){
                     self.terminals(form_data.terminals);
                     self.selectedTerminal(self.getDefaultSelectedTerminal());
                 }
                 return self;
             },
             getDefaultSelectedTerminal: function(){
                 var self = this;
                 var terminals = self.terminals();
                 var defaultSelected = '';
                 if(terminals && (terminals.length > 0)){
                     var selectedTerminal = Helper.getLocalConfig(self.SELECTED_TERMINAL_PATH);
                     if(selectedTerminal){
                        $.each(terminals, function(index, terminal){
                            if(terminal.value == selectedTerminal){
                                defaultSelected =  selectedTerminal;
                            }
                        });
                     }else{
                         var terminal = terminals[0];
                         defaultSelected = terminal.value;
                     }
                 }
                 return defaultSelected;
             },
             placeOrderBefore: function(data){
                 var self = this;
                 if (data && data.increment_id && (CheckoutModel.paymentCode() == self.CODE)) {
                     data.sync_params.payment.terminalId = self.selectedTerminal();
                 }
             },
             placeOrderOnlineBefore: function(params){
                 var self = this;
                 if (CheckoutModel.paymentCode() == self.CODE) {
                     params.payment.terminalId = self.selectedTerminal();
                 }
             }
         };
         return PaynlInstoreModel.initialize();
    }
);