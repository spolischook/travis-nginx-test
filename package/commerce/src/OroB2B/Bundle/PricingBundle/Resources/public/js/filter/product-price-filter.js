/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/number-range-filter'
], function($, _, __, NumberRangeFilter) {
    'use strict';

    var ProductPriceFilter;

    /**
     * Product price filter
     *
     * @export  oro/filter/product-price-filter
     * @class   oro.filter.ProductPriceFilter
     * @extends oro.filter.NumberRangeFilter
     */
    ProductPriceFilter = NumberRangeFilter.extend({
        /**
         * @property
         */
        unitTemplate: _.template($('#product-price-filter-template').html()),

        /**
         * @property {Array}
         */
        unitChoices: [],

        /**
         * @property {Object}
         */
        criteriaValueSelectors: {
            unit: 'input[name="unit"]',
            type: 'input.name_input'
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            ProductPriceFilter.__super__.initialize.apply(this, arguments);

            _.defaults(this.emptyValue, {
                unit: (_.isEmpty(this.unitChoices) ? '' : _.first(this.unitChoices).value),
                type: (_.isEmpty(this.choices) ? '' : _.first(this.choices).value)
            });

            _.defaults(this.criteriaValueSelectors, ProductPriceFilter.__super__.criteriaValueSelectors);
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            this._checkAppendFilter();
            return ProductPriceFilter.__super__._renderCriteria.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.unitChoices;
            return ProductPriceFilter.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.unit, value.unit);
            return ProductPriceFilter.__super__._writeDOMValue.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var dataValue = ProductPriceFilter.__super__._readDOMValue.apply(this, arguments);
            dataValue.unit = this._getInputValue(this.criteriaValueSelectors.unit);
            return dataValue;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();

            if (this.isEmptyValue()) {
                return this.placeholder;
            }

            var hintValue = ProductPriceFilter.__super__._getCriteriaHint.apply(this, arguments);

            var unitOption = '';
            if (!_.isUndefined(value.unit) && value.unit) {
                unitOption = _.findWhere(this.unitChoices, {value: value.unit}).shortLabel;
            }

            hintValue += ' ' + __('orob2b.pricing.filter.product_price.per') + ' ' + unitOption;

            return hintValue;
        },

        /**
         * @inheritDoc
         */
        _updateValueField: function() {
            ProductPriceFilter.__super__._updateValueField.apply(this, arguments);

            var type = this.$(this.criteriaValueSelectors.type).val();

            this.$('.product-price-unit-filter-separator').toggle(!this.isEmptyType(type));
        },

        /**
         * @inheritDoc
         */
        _onClickChoiceValue: function(e) {
            var target = $(e.currentTarget);

            if (target.closest('.product-price-unit-filter').get(0)) {
                target.parent().parent().find('li').each(function() {
                    $(this).removeClass('active');
                });
                target.parent().addClass('active');

                var parentDiv = target.parent().parent().parent();
                var type = target.attr('data-value');
                var choiceName = target.html();

                var criteriaValues = this.$(this.criteriaValueSelectors.unit).val(type);
                this.fixSelects();
                criteriaValues.trigger('change');
                choiceName += this.caret;
                parentDiv.find('.dropdown-toggle').html(choiceName);

                this._handleEmptyFilter(type);

                e.preventDefault();
            } else {
                return ProductPriceFilter.__super__._onClickChoiceValue.apply(this, arguments);
            }
        },

        /**
         * @private
         */
        _checkAppendFilter: function() {
            if (this._appendFilter !== this._appendUnitFilter) {
                this._appendUnitFilter._appendFilter = this._appendFilter;
                this._appendFilter = this._appendUnitFilter;
            }
        },

        /**
         * @private
         */
        _appendUnitFilter: function($filter) {
            var value = _.extend({}, this.emptyValue, this.value);
            var selectedChoiceLabel = '';
            var $updateBtn;
            var $unitFilter;

            if (!_.isEmpty(this.unitChoices)) {
                selectedChoiceLabel = _.find(this.unitChoices, function(choice) {
                    return (choice.value === value.unit);
                }).label;
            }

            $unitFilter = $(this.unitTemplate({
                choices: this.unitChoices,
                selectedChoice: value.unit,
                selectedChoiceLabel: selectedChoiceLabel
            }));

            $updateBtn = $filter.find('.filter-update');
            $unitFilter.prepend($filter).append($updateBtn);

            this._appendUnitFilter._appendFilter.call(this, $unitFilter);
        }
    });

    return ProductPriceFilter;
});
