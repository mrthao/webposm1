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
        'helper/general',
        'model/appConfig',
        'model/session',
        'model/shift/tills'
    ],
    function ($, ko, Component, Helper, AppConfig, Session, Tills) {
        "use strict";
        return Component.extend({
            /**
             * Default values
             */
            defaults: {
                template: 'ui/shift/tills',
                element_id: 'select_cash_drawer_popup',
                content_wrapper_id: 'select_cash_drawer_popup_content'
            },
            /**
             * List available cash drawer
             */
            tills: Tills.tills,
            /**
             * Current cash drawer
             */
            currentTill: Tills.currentTill,
            /**
             * Check if no cash drawer available
             */
            isTillsEmpty: Tills.isTillsEmpty,
            /**
             * Check if cash drawer enabled
             */
            isEnable: Tills.isEnable,
            /**
             * Initialize
             */
            initialize: function () {
                this._super();
                var self = this;
                if(self.isEnable()){
                    self.validate();
                    self.observerEvent(AppConfig.EVENT.SHOW_POPUP_SELECT_CASH_DRAWER, function () {
                        self.show();
                    });
                    self.observerEvent(AppConfig.EVENT.CLEAR_SESSION_AFTER, function () {
                        Tills.clear();
                    });
                }
                return self;
            },

            /**
             * Select cash drawer
             * @param till
             */
            selectTill: function(till){
                var self = this;
                if(till && till.id){
                    var selected = Tills.select(till.id);
                    if(selected){
                        self.dispatchEvent(AppConfig.EVENT.SELECT_CASH_DRAWER_AFTER, '');
                        self.hide();
                    }
                }
            },

            /**
             * Show popup
             */
            show: function(){
                var self = this;
                var el = $('#'+self.element_id);
                if(el.length > 0){
                    var error = Tills.validate();
                    el.addClass(AppConfig.CLASS.ACTIVE);
                    el.posOverlay({
                        overlayDismiss: (error)?false:true
                    });
                }
                $('.notification-bell').hide();
            },
            /**
             * Hide popup
             */
            hide: function(){
                var self = this;
                var el = $('#'+self.element_id);
                if(el.length > 0){
                    el.removeClass(AppConfig.CLASS.ACTIVE);
                    el.posOverlay({
                        autoOpen: false,
                        overlayDismiss: false
                    }).hideOverlay();
                }
                $('.notification-bell').show();
            },
            /**
             * Check and show popup select cash drawer if needed
             */
            validate: function(){
                var self = this;
                var error = Tills.validate();
                if(error){
                    self.show();
                }else{
                    self.hide();
                }
            },
            /**
             * Logout
             */
            logout: function(){
                var self = this;
                self.dispatchEvent(AppConfig.EVENT.LOGOUT_WITHOUT_CONFIRM, '');
            }
        });
    }
);
