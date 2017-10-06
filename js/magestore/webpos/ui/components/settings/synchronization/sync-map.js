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
        'eventManager',
        'helper/general'
    ],
    function ($, eventManager, Helper) {
        "use strict";
        var maps = [
            {
                sort_order: 110,
                label: 'Order',
                model: 'model/sales/order',
                limitPage: 2,
                filter: {
                    field: 'created_at',
                    config: 'webpos/offline/order_limit',
                    is_config: true,
                    datetime: 'day',
                    condition: 'gt'
                }
            },
            {
                sort_order: 100,
                label: 'Country',
                model: 'model/directory/country',
                limitPage: 0
            },
            {
                sort_order: 90,
                label: 'Currency',
                model: 'model/directory/currency',
                limitPage: 0
            },
            {
                sort_order: 80,
                label: 'Customer',
                model: 'model/customer/customer',
                limitPage: 2,
                filter: {
                    field: 'updated_at',
                    config: 'webpos/updated/customer',
                    datetime: true,
                    mode: 'finish',
                    condition: 'gteq'
                }
            },
            {
                sort_order: 30,
                label: 'Product',
                model: 'model/catalog/product',
                limitPage: 2,
                filter: {
                    field: 'updated_at',
                    config: 'webpos/updated/product',
                    datetime: true,
                    mode: 'finish',
                    condition: 'gteq'
                },
                pageSize:300
            },
            {
                sort_order: 20,
                label: 'Category',
                model: 'model/catalog/category',
                limitPage: 0
            },
            {
                sort_order: 10,
                label: 'Configuration',
                model: 'model/config/config',
                limitPage: 0
            }
        ];

        if(Helper.isStoreCreditEnable()){
            maps.push({
                sort_order: 7,
                label: 'Customer Credit',
                model: 'model/checkout/integration/data/store-credit',
                limitPage: 0
            });
        }
        if(Helper.isRewardPointsEnable()){
            maps.push({
                sort_order: 8,
                label: 'Customer Points',
                model: 'model/checkout/integration/data/reward-points',
                push: false
            });
            maps.push({
                sort_order: 9,
                label: 'Reward Point Rates',
                model: 'model/checkout/integration/rewardpoints/rate',
                push: false
            });
        }
        eventManager.dispatch('sync_prepare_maps', {'maps': maps});

        return maps;
    }
);