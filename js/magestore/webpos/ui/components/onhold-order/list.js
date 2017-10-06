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
        'model/sales/order-factory',
        'view/layout',
        'view/base/list/collection-list',
        'eventManager',
        'helper/price',
        'helper/datetime'
    ],
    function ($, ko, OrderFactory, ViewManager,
              listAbstract, Event, priceHelper, datetimeHelper) {
        "use strict";

        return listAbstract.extend({
            items: ko.observableArray([]),
            collection: '',
            isShowHeader: false,
            isSearchable: true,
            pageSize: 100,
            curPage: 1,
            selectedOrder: ko.observable(null),
            searchKey: '',
            groupDays: [],
            statusBtn: '.wrap-status-orders ul li',
            statusArray: ['onhold'],

            defaults: {
                template: 'ui/onhold-order/list',
            },

            initialize: function () {
                this._super();
                this.listenMenuShowContainerAfterEvent();
            },

            _processResponse: function(response){
                var self = this;
                var items = [];
                var orderList = response.items;
                var dayIndex = -1;
                $.each(orderList, function(index,value){
                    if(index==0)
                        if(value)
                            self.loadItem(value);
                    var createdAt = value.created_at;
                    var day = createdAt.split(' ')[0];
                    if(self.groupDays.indexOf(day)==-1){
                        dayIndex++;
                        self.groupDays.push(day);
                        items[dayIndex] = {};
                        items[dayIndex].day = day;
                        items[dayIndex].orderItems = [];
                        items[dayIndex].orderItems.push(value);
                    }else{
                        items[self.groupDays.indexOf(day)].orderItems.push(value);
                    }
                });
                return items;
            },

            _prepareItems: function () {
                var self = this;
                this.resetData();
                this.groupDays = [];
                this.collection = OrderFactory.get().setMode('offline').getCollection();
                if(this.searchKey)
                    this.collection.addFieldToFilter(
                        [
                            ['increment_id', this.searchKey, 'like'],
                            ['customer_email', this.searchKey, 'like'],
                            ['customer_firstname', this.searchKey, 'like'],
                            ['customer_lastname', this.searchKey, 'like'],
                            ['customer_telephone', this.searchKey, 'like']
                        ]
                    );
                this.collection.addFieldToFilter('status', this.statusArray, 'in');
                this.collection.setOrder('entity_id', 'DESC');
                var deferred = $.Deferred();
                this.collection.load(deferred);
                deferred.done(function(response){
                    var items = self._processResponse(response);
                    self.setItems(items);
                });
            },

            orderSearch: function(data, event){
                this.collection.reset();
                this.searchKey = event.target.value;
                this._prepareItems();
            },

            filterStatus: function(data, event){
                var el = $(event.currentTarget);
                if(el.hasClass('selected')) {
                    el.removeClass('selected');
                    this.statusArray.splice(this.statusArray.indexOf(el.attr('status')), 1);
                }
                else {
                    el.addClass('selected');
                    this.statusArray.push(el.attr('status'));
                }
                this._prepareItems();
            },

            resetFilterStatus: function(){
                var self = this;
                $.each($(this.statusBtn), function(index, value){
                    $(value).addClass('selected');
                    self.statusArray.splice(self.statusArray.indexOf($(value).attr('status')), 1);
                    self.statusArray.push($(value).attr('status'));
                })
            },

            loadItem: function(data, event){
                ViewManager.getSingleton('ui/components/onhold-order/detail').setData(data, this);
                this.selectedOrder(data.entity_id);
            },

            updateOrderListData: function (item) {
                var items = this.items();
                for (var index in items){
                    var createdAt = item.created_at;
                    var day = createdAt.split(' ')[0];
                    if(day == items[index].day){
                        for(var i in items[index].orderItems){
                            if(item.entity_id == items[index].orderItems[i].entity_id) {
                                items[index].orderItems[i] = item;
                                this.resetData();
                                this.setItems(items);
                                this.loadItem(item);
                            }
                        }
                    }
                }
            },

            formatPrice: function(price){
                return priceHelper.formatPrice(price);
            },

            getCustomerName: function(data){
                if(data.customer_firstname && data.customer_lastname)
                    return data.customer_firstname + ' ' + data.customer_lastname;
                return data.customer_email;
            },

            getGrandTotal: function(data){
                return priceHelper.convertAndFormat(data.base_grand_total);
            },

            getCreatedAt: function(data){
                return this.getTime(data.created_at);
            },

            /**
             * return a date time with format: 15:26 PM
             * @param dateString
             * @returns {string}
             */
            getTime: function(dateString) {
                return datetimeHelper.getTime(dateString);
            },

            render: function() {
                this._render();
            },

            listenMenuShowContainerAfterEvent: function () {
                var self = this;
                Event.observer('on_hold_orders_show_container_after', function (event, eventData) {
                    self.render();
                });
            },
            formatDateGroup: function (dateString) {
                var date = "";
                if (!dateString) {
                    date = new Date();
                } else {
                    date = new Date(dateString);
                }
                var month = date.getMonth() + 1;
                if (month < 10) {
                    month = "0" + month;
                }
                return date.getDate() + '/' + month + '/' + date.getFullYear();
            },
        });
    }
);