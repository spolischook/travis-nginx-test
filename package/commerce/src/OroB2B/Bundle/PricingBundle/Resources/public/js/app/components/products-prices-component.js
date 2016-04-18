define(function(require) {
    'use strict';

    var ProductsPricesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ProductsPricesComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            account: null,
            currency: null,
            website: null,
            tierPrices: null,
            tierPricesRoute: '',
            matchedPrices: {},
            matchedPricesRoute: '',
            requestKeys: {
                ACCOUNT: 'account_id',
                WEBSITE: 'websiteId',
                CURRENCY: 'currency'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$el = this.options._sourceElement;

            mediator.on('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.on('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);

            mediator.on('pricing:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.on('pricing:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            mediator.on('update:currency', this.setCurrency, this);
            mediator.on('update:account', this.setAccount, this);
            mediator.on('update:website', this.setWebsite, this);

            if (this.options.$priceList) {
                this.options.$priceList.change(_.bind(this.reloadPrices, this));
            }
        },

        /**
         * @param {Function} callback
         */
        getProductsTierPrices: function(callback) {
            callback(this.options.tierPrices);
        },

        reloadPrices: function() {
            this.loadProductsTierPrices(this.getProductsId(), function(response) {
                mediator.trigger('pricing:refresh:products-tier-prices', response);
            });

            this.loadLineItemsMatchedPrices(this.getLineItems(), function(response) {
                mediator.trigger('pricing:refresh:line-items-matched-prices', response);
            });
        },

        /**
         * @param {Array} products
         * @param {Function} callback
         */
        loadProductsTierPrices: function(products, callback) {
            this.joinSubrequests(this.loadProductsTierPrices, products, callback, _.bind(function(products, callback) {
                var params = {
                    product_ids: products
                };
                params[this.options.requestKeys.CURRENCY] = this.getCurrency();
                params[this.options.requestKeys.ACCOUNT] = this.getAccount();
                params[this.options.requestKeys.WEBSITE] = this.getWebsite();

                $.get(routing.generate(this.options.tierPricesRoute, params), callback);
            }, this));
        },

        /**
         * @param {Array} items
         * @param {Function} callback
         */
        loadLineItemsMatchedPrices: function(items, callback) {
            this.joinSubrequests(this.loadLineItemsMatchedPrices, items, callback, _.bind(function(items, callback) {
                var params = {
                    items: items
                };
                params[this.options.requestKeys.CURRENCY] = this.getCurrency();
                params[this.options.requestKeys.ACCOUNT] = this.getAccount();
                params[this.options.requestKeys.WEBSITE] = this.getWebsite();

                $.ajax({
                    url: routing.generate(this.options.matchedPricesRoute, params),
                    type: 'GET',
                    success: callback,
                    error: function(response) {
                        callback(response);
                    }
                });
            }, this));
        },

        joinSubrequests: function(storage, data, callback, request) {
            storage.timeoutId = storage.timeoutId || null;
            storage.data = storage.data || [];
            storage.callbacks = storage.callbacks || [];

            storage.data = _.union(storage.data, data);
            storage.callbacks.push(callback);

            if (storage.timeoutId) {
                clearTimeout(storage.timeoutId);
            }

            storage.timeoutId = setTimeout(function() {
                var data = storage.data;
                var callbacks = storage.callbacks;

                storage.timeoutId = null;
                storage.data = [];
                storage.callbacks = [];

                request(data, function(response) {
                    _.each(callbacks, function(callback) {
                        callback(response);
                    });
                });
            }, 50);
        },

        /**
         * @returns {Array} products ID
         */
        getProductsId: function() {
            return _.map(this.getLineItems(), function(lineItem) {
                return lineItem.product;
            });
        },

        /**
         * @param {Function} callback
         */
        getLineItemsMatchedPrices: function(callback) {
            callback(this.options.matchedPrices);
        },

        /**
         * @returns {Array} line items
         */
        getLineItems: function() {
            var items = [];
            mediator.trigger('pricing:collect:line-items', items);
            return items;
        },

        getCurrency: function() {
            return this.options.currency;
        },

        setCurrency: function(val) {
            this.options.currency = val;
            this.reloadPrices();
        },

        getAccount: function() {
            return this.options.account;
        },

        setAccount: function(val) {
            this.options.account = val;
            this.reloadPrices();
        },

        getWebsite: function() {
            return this.options.website;
        },

        setWebsite: function(val) {
            this.options.website = val;
            this.reloadPrices();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('pricing:get:products-tier-prices', this.getProductsTierPrices, this);
            mediator.off('pricing:load:products-tier-prices', this.loadProductsTierPrices, this);

            mediator.off('pricing:get:line-items-matched-prices', this.getLineItemsMatchedPrices, this);
            mediator.off('pricing:load:line-items-matched-prices', this.loadLineItemsMatchedPrices, this);

            mediator.off('update:currency', this.handleCurrencyChange, this);

            ProductsPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductsPricesComponent;
});
