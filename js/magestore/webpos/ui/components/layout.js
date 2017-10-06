
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
var arrayBlock = [
    'require',
    'ui/components/base/abstract',
    'ui/components/catalog/product/detail-popup',
    'ui/components/order/list',
    'ui/components/order/detail',
    'ui/components/order/view/payment',
    'ui/components/order/action',
    // 'ui/components/sales/order/sendemail',
    // 'ui/components/sales/order/comment',
    // 'ui/components/sales/order/invoice',
    // 'ui/components/sales/order/shipment',
    // 'ui/components/sales/order/creditmemo',
    // 'ui/components/sales/order/cancel',
    // 'ui/components/checkout/cart/discountpopup',
    // 'ui/components/checkout/customer/add-billing-address',
    // 'ui/components/checkout/cart',
    // 'ui/components/checkout/customer/edit-customer',
    // 'ui/components/container',
    'ui/components/catalog/product-list',
    // 'ui/components/sales/order/hold-view',
    // 'ui/components/checkout/checkout/renderer/payment',
    // 'ui/components/shift/cash-transaction/activity',
    'ui/components/catalog/category/breadcrumbs',
    // 'ui/components/checkout/checkout/payment_popup',
    // 'ui/components/checkout/checkout/shipping',
    // 'ui/components/checkout/checkout/payment',
    // 'ui/components/checkout/checkout/payment_selected',
    // 'ui/components/checkout/checkout/payment_creditcard',
    // 'ui/components/checkout/checkout/swipe/jquery.cardswipe',
    'ui/components/checkout/customer/add-shipping-address'
    // 'ui/components/checkout/checkout/receipt',
    // 'ui/components/customer/customer-view',
    // 'ui/components/catalog/category/cell-grid',
    // 'ui/components/checkout/customer/add-customer',
    // 'ui/components/shift/shift/shift-listing',
    // 'ui/components/shift/shift/shift-detail',
    // 'ui/components/shift/sales-summary/sales-summary',
    // 'ui/components/shift/sales-summary/zreport',
    // 'ui/components/shift/cash-transaction/cash-adjustment',
    // 'ui/components/shift/shift/close-shift'
];

define(
    arrayBlock
    ,
    function (require,
              view_base_abstract,
              view_catalog_product_detailpopup,
              ui_components_sales_order_list,
              ui_components_sales_order_view,
              ui_components_sales_order_view_payment,
              ui_components_sales_order_action,
              // view_sales_order_sendemail,
              // view_sales_order_comment,
              // view_sales_order_invoice,
              // view_sales_order_shipment,
              // view_sales_order_creditmemo,
              // view_sales_order_cancel,
              // view_checkout_cart_discountpopup,
              // view_checkout_customer_addbillingaddress,
              // view_checkout_cart,
              // view_checkout_customer_editcustomer,
              // view_container,
              view_catalog_productlist,
              // view_sales_order_holdview,
              // view_checkout_checkout_renderer_payment,
              // view_shift_cashtransaction_activity,
              view_catalog_category_breadcrumbs,
              // view_checkout_checkout_payment_popup,
              // view_checkout_checkout_shipping,
              // view_checkout_checkout_payment,
              // view_checkout_checkout_payment_selected,
              // view_checkout_checkout_payment_creditcard,
              // view_checkout_checkout_swipe_jquerycardswipe,
              view_checkout_customer_addshippingaddress
              // view_checkout_checkout_receipt,
              // view_customer_customerview,
              // view_catalog_category_cellgrid,
              // view_checkout_customer_addcustomer,
              // view_shift_shift_shiftlisting,
              // view_shift_shift_shiftdetail,
              // view_shift_salessummary_salessummary,
              // view_shift_salessummary_zreport,
              // view_shift_cashtransaction_cashadjustment,
              // view_shift_shift_closeshift
    ) {
        "use strict";

        return {

            getSingleton: function (viewName) {
                var view = this._convertModelPath(viewName);
                if(!window.webposViews) {
                    window.webposViews = {};
                }
                if(!window.webposViews[view]) {
                    var viewClass = require(viewName);
                    window.webposViews[view] = viewClass();
                }
                return window.webposViews[view];
            },

            /**
             * convert model name to key
             *
             * @param string modelName
             * @returns string
             */
            _convertModelPath: function (modelName) {
                modelName = modelName.replace(/\//gi, '_');
                modelName = modelName.replace(/-/gi, '');
                modelName = modelName.replace(/\./gi, '');
                return modelName;
            }
        };
    }
);



























































