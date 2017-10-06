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
        'jquery',
        'helper/general',
        'model/resource-model/magento-rest/shift/cash-transaction'
    ],
    function (ko, $, Helper, TransactionResource) {
        "use strict";

        var TransactionModel = {
            transactions: ko.observableArray(),
            initialize: function(){
                var self = this;
                return self;
            },
            gets: function(cashDrawerId){
                var self = this;
                var deferred = $.Deferred();
                var params = {
                    till_id: (cashDrawerId)?cashDrawerId:0
                };
                TransactionResource().setPush(true).setLog(false).getList(params,deferred);
                deferred.done(function(response){
                    if(response.status && response.data && response.data.items){
                        self.transactions(response.data.items);
                    }
                });
                return deferred;
            },
            save: function(data){
                var self = this;
                var deferred = $.Deferred();
                var params = {
                    data: data
                };
                TransactionResource().setPush(true).setLog(false).saveTransaction(params,deferred);
                deferred.done(function(response){
                    if(response.status && response.data){
                        self.gets(data.till_id);
                    }
                });
                return deferred;
            }
        };
        return TransactionModel.initialize();
    }
);