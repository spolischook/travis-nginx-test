/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var PriceListCurrencyLimitationComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    PriceListCurrencyLimitationComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            priceListSelector: 'input[name$="[priceList]"]',
            currencySelector: 'select[name$="[price][currency]"]',
            container: '.oro-item-collection',
            currenciesRoute: 'orob2b_pricing_price_list_currency_list'
        },

        /**
         * @property {array}
         */
        currencies: {},

        /**
         * @property {array}
         */
        systemSupportedCurrencyOptions: {},

        /**
         * @property {Object}
         */
        $priceListSelect: null,

        /**
         * @property {Object}
         */
        $currencySelect: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.loadingMaskView = new LoadingMaskView({container: this.$elem});
            this.currencies = this.$elem.closest(options.container).data('currencies');
            this.$priceListSelect = this.$elem.find(options.priceListSelector);
            this.$currencySelect = this.$elem.find(options.currencySelector);
            this.$currencySelect.find('option').clone().each(
                _.bind(
                    function(idx, option) {
                        this.systemSupportedCurrencyOptions[option.value] = option;
                    },
                    this
                )
            );

            this.prepareCurrencySelect(false);
            this.$elem.on(
                'change',
                options.priceListSelector,
                _.bind(
                    function() {
                        this.prepareCurrencySelect(true);
                    },
                    this
                )
            );
        },

        /**
         * Prepare currency list select for selected price list
         *
         *  @param {Boolean} selectFirst
         */
        prepareCurrencySelect: function(selectFirst) {
            var priceListId = this.$priceListSelect.val();
            var self = this;

            if (!priceListId) {
                this.$currencySelect.find('option[value=""]').show();
                this.$currencySelect.attr('disabled', 'disabled');
                this.$currencySelect.val('');
                this.$currencySelect.trigger('change');
                return;
            }

            if (_.has(this.currencies, priceListId)) {
                this.handleCurrencies(this.currencies[priceListId], selectFirst);
            } else {
                $.ajax({
                    url: routing.generate(this.options.currenciesRoute, {'id': priceListId}),
                    type: 'GET',
                    beforeSend: function() {
                        self.loadingMaskView.show();
                    },
                    success: function(response) {
                        var priceListCurrencies = _.keys(response);
                        self.currencies[priceListId] = priceListCurrencies;
                        self.$elem.closest(self.options.container).data('currencies', self.currencies);
                        self.handleCurrencies(priceListCurrencies, selectFirst);
                    },
                    complete: function() {
                        self.loadingMaskView.hide();
                    },
                    error: function(xhr) {
                        Error.handle({}, xhr, {enforce: true});
                    }
                });
            }
        },

        /**
         * @param {array} priceListCurrencies
         * @param {Boolean} selectFirst
         */
        handleCurrencies: function(priceListCurrencies, selectFirst) {
            // Add empty key for empty value placeholder
            if (priceListCurrencies.indexOf('') === -1) {
                priceListCurrencies.unshift('');
            }

            var newOptions = _.filter(
                this.systemSupportedCurrencyOptions,
                function(option, key) {
                    return _.indexOf(priceListCurrencies, key) !== -1;
                }
            );

            this.$currencySelect.html(newOptions);
            this.$currencySelect.find('option[value=""]').hide();
            this.$currencySelect.removeAttr('disabled');

            if (selectFirst) {
                this.$currencySelect.val(priceListCurrencies[1]);
                this.$currencySelect.trigger('change');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$elem.off();

            PriceListCurrencyLimitationComponent.__super__.dispose.call(this);
        }
    });

    return PriceListCurrencyLimitationComponent;
});
