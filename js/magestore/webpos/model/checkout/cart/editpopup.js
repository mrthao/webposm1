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
        'model/checkout/cart'
    ],
    function ($, ko, CartModel) {
        "use strict";
        return {
            itemId: ko.observable(),
            setItem: function(item){
                this.itemId(item.item_id());
            },
            getItemId: function(){
                return this.itemId();
            },
            getEditingItemId: function(){
                return this.getItemId();
            },
            getData: function(key){
                return CartModel.getItemData(this.getItemId(), key);
            },
            setData: function(key,value){
                CartModel.updateItem(this.getItemId(), key, value);
            }
        };
    }
);