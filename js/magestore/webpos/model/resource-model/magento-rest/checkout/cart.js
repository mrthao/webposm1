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
        'model/resource-model/magento-rest/checkout/abstract'
    ],
    function (onlineAbstract) {
        "use strict";

        return onlineAbstract.extend({
            initialize: function () {
                this._super();
                this.apiSaveCartUrl = "webpos/cart/save";
                this.apiRemoveCartUrl = "webpos/cart/removeCart";
                this.apiRemoveItemUrl = "webpos/cart/removeItem";
            },
            getCallBackEvent: function(key){
                switch(key){
                    case "saveCart":
                        return "save_cart_online_after";
                    case "removeCart":
                        return "remove_cart_online_after";
                    case "removeItem":
                        return "remove_item_online_after";
                }
            },
            setApiUrl: function(key,value){
                switch(key){
                    case "apiSaveCartUrl":
                        this.apiSaveCartUrl = value;
                        break;
                    case "apiRemoveCartUrl":
                        this.apiRemoveCartUrl = value;
                        break;
                    case "apiRemoveItemUrl":
                        this.apiRemoveItemUrl = value;
                        break;
                }
            },
            getApiUrl: function(key){
                switch(key){
                    case "apiSaveCartUrl":
                        return this.apiSaveCartUrl;
                    case "apiRemoveCartUrl":
                        return this.apiRemoveCartUrl;
                    case "apiRemoveItemUrl":
                        return this.apiRemoveItemUrl;
                }
            },
            saveCartBeforeCheckout: function(params,deferred){
                var apiUrl = this.getApiUrl("apiSaveCartUrl");
                var callBackEvent = this.getCallBackEvent("saveCart");
                this.callApi(apiUrl, params, deferred, callBackEvent);
            },
            saveCart: function(params,deferred){
                var apiUrl = this.getApiUrl("apiSaveCartUrl");
                this.callApi(apiUrl, params, deferred);
            },
            removeCart: function(params,deferred){
                var apiUrl = this.getApiUrl("apiRemoveCartUrl");
                var callBackEvent = this.getCallBackEvent("removeCart");
                this.callApi(apiUrl, params, deferred, callBackEvent);
            },
            removeItem: function(params,deferred){
                var apiUrl = this.getApiUrl("apiRemoveItemUrl");
                var callBackEvent = this.getCallBackEvent("removeItem");
                this.callApi(apiUrl, params, deferred, callBackEvent);
            }
        });
    }
);