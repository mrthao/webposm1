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
        'view/base/grid/abstract',
        'model/shift/data/shift'
    ],
    function ($, ko, listAbstract, ShiftModel) {
        "use strict";

        return listAbstract.extend({
            items: ShiftModel.salesByPayments,
            hasData: ko.pureComputed(function(){
                return (ShiftModel.salesByPayments().length > 0)?true:false;
            }),
            cashDrawerData: ShiftModel.cashDrawerData,
            defaults: {
                template: 'ui/shift/sales-summary/sales-summary',
            },
            initialize: function () {
                this._super();
                this._render();
            },
            generatePaymentCode: function (paymentMethod) {
                var posPayment = ['cashforpos', 'codforpos', 'ccforpos', 'cp1forpos', 'cp2forpos'];
                paymentMethod = ($.inArray(paymentMethod, posPayment) >= 0)?paymentMethod:'cp2forpos';
                return "icon-iconPOS-payment-" + paymentMethod;
            }
        });
    }
);
