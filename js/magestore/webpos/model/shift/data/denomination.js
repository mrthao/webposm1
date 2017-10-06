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
        'uiClass',
        'helper/price'
    ],
    function (ko, UiClass, PriceHelper) {
        "use strict";

        return UiClass.extend({
            name: '',
            value: 0,
            qty: 0,
            qtyChanged: ko.observable(false),
            initialize: function () {
                this._super();
                var self = this;
                self.total = ko.pureComputed(function(){
                    var total = parseFloat(self.value) * parseFloat(self.qty);
                    return (self.qtyChanged())?total:total;
                });
                self.total_formated = ko.pureComputed(function(){
                    return PriceHelper.convertAndFormat(self.total());
                });
                self.value_formated = ko.pureComputed(function(){
                    return PriceHelper.convertAndFormat(self.value);
                });
            }
        });
    }
);