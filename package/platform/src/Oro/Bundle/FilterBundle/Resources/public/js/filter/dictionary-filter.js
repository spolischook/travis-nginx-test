define(function(require) {
    'use strict';

    var DictionaryFilter;
    var $ = require('jquery');
    var routing = require('routing');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ChoiceFilter = require('oro/filter/choice-filter');
    var messenger = require('oroui/js/messenger');
    var tools = require('oroui/js/tools');
    require('jquery.select2');

    /**
     * Multiple select filter: filter values as multiple select options
     *
     * @export  oro/filter/dictionary-filter
     * @class   oro.filter.DictionaryFilter
     * @extends oro.filter.ChoiceFilter
     */
    DictionaryFilter = ChoiceFilter.extend({
        /**
         * select2 will apply to element with this selector
         */
        elementSelector: '.select-values-autocomplete',

        /**
         * Filter selector template
         *
         * @property
         */
        templateSelector: '#dictionary-filter-template',

        /**
         * Template selector for dictionary field parts
         *
         * @property
         */
        fieldTemplateSelector: '#select-field-template',

        /**
         * Maximum value of count items for drop down menu.
         * If count values will be bigger than this value then
         * this filter will use select2 with autocomplete
         */
        maxCountForDropDownMode: 10,

        /**
         * Selectors for filter data
         *
         * @property
         */
        criteriaValueSelectors: {
            type: 'input[type="hidden"]:last'
        },

        filterParams: null,

        class: null,

        isInitSelect2: false,

        previousData: [],

        /**
         * Data of selected values
         */
        selectedData: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            if (this.filterParams) {
                this.dictionaryClass = this.filterParams.class.replace(/\\/g, '_');
            } else {
                this.dictionaryClass = this.class.replace(/\\/g, '_');
            }

            this.listenTo(this, 'renderCriteriaLoadValues', this.renderCriteriaLoadValues);
            this.listenTo(this, 'updateCriteriaLabels', this.updateCriteriaLabels);

            DictionaryFilter.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        reset: function() {
            DictionaryFilter.__super__.reset.apply(this, arguments);
            var select2element = this.$el.find(this.elementSelector);
            var data = select2element.inputWidget('data');
            if (data) {
                this.previousData = data;
            }
            select2element.inputWidget('data',  null);
        },

        /**
         * Init render
         */
        render: function() {
            this.renderDeferred = $.Deferred();
            this._wrap('');
            if (this.$el.html() === '') {
                this._renderCriteria();
            }
        },

        /**
         * Execute ajax request to get data of entities by ids.
         *
         * @param successEventName
         */
        loadValuesById: function(successEventName) {
            var self = this;
            $.ajax({
                url: routing.generate(
                    'oro_dictionary_value',
                    {
                        dictionary: this.dictionaryClass
                    }
                ),
                data: {
                    'keys': this.value.value
                },
                success: function(response) {
                    self.trigger(successEventName, response);
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });
        },

        /**
         * Handler for event 'renderCriteriaLoadValues'
         *
         * @param response
         */
        renderCriteriaLoadValues: function(response) {
            this.updateLocalValues(response.results);

            this._writeDOMValue(this.value);
            this.applySelect2();
            this._updateCriteriaHint();
            this.renderDeferred.resolve();
        },

        /**
         * Handler for event 'updateCriteriaLabels'
         *
         * @param response
         */
        updateCriteriaLabels: function(response) {
            this.updateLocalValues(response.results);
            this.$(this.elementSelector).inputWidget('data', this.getDataForSelect2());
            this._updateCriteriaHint();
        },

        /**
         * Update privet variables selectedData and value
         *
         * @param values
         *
         * @returns {oro.filter.DictionaryFilter}
         */
        updateLocalValues: function(values) {
            var ids = [];
            _.each(values, function(item) {
                ids.push(item.id);
                this.selectedData[item.id] = item;
            }, this);

            this.value.value = ids;

            return this;
        },

        /**
         * @inheritDoc
         */
        _renderCriteria: function() {
            this.renderTemplate();
            this.loadValuesById('renderCriteriaLoadValues');
        },

        /**
         * Render template for filter
         */
        renderTemplate: function() {
            var value = _.extend({}, this.emptyValue, this.value);
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this.choices)) {
                var foundChoice = _.find(this.choices, function(choice) {
                    return (parseInt(choice.value) === parseInt(value.type));
                });
                selectedChoiceLabel = foundChoice.label;
            }
            var parts = this._getParts();

            var $filter = $(this.template({
                parts: parts,
                isEmpty: false,
                showLabel: this.showLabel,
                label: this.label,
                selectedChoiceLabel: selectedChoiceLabel,
                selectedChoice: value.type,
                choices: this.choices,
                name: this.name
            }));

            this._appendFilter($filter);
        },

        /**
         * init select2 for input
         */
        applySelect2: function() {
            var self = this;
            var select2Config = this.getSelect2Config();
            var select2element = this.$el.find(this.elementSelector);
            var values = this.getDataForSelect2();

            select2element.removeClass('hide');
            select2element.attr('multiple', 'multiple');
            select2element.inputWidget('create', 'select2', {initializeOptions: select2Config});
            self.isInitSelect2 = true;
            if (this.templateTheme) {
                select2element.on('change', function() {
                    self.applyValue();
                });
            }
            select2element.inputWidget('data',  values);

            this._criteriaRenderd = true;
        },

        /**
         * Return config for select2
         */
        getSelect2Config: function() {
            var config =  {
                multiple: true,
                containerCssClass: 'dictionary-filter',
                ajax: {
                    url: routing.generate(
                        'oro_dictionary_search',
                        {
                            dictionary: this.dictionaryClass
                        }
                    ),
                    dataType: 'json',
                    delay: 250,
                    type: 'POST',
                    data: function(params) {
                        return {
                            q: params // search term
                        };
                    },
                    results: function(data) {
                        return {
                            results: data.results
                        };
                    }
                },
                dropdownAutoWidth: true,
                escapeMarkup: function(markup) { return markup; }, // let our custom formatter work
                minimumInputLength: 0,
                placeholder: __('Choose values')
            };

            if (this.templateTheme === '') {
                config.width = 'resolve';
            }

            return config;
        },

        /**
         * Convert data to format for select2
         *
         * @returns {Array}
         */
        getDataForSelect2: function() {
            var values = [];
            _.each(this.value.value, function(value) {
                var item = this.selectedData[value];

                if (item) {
                    values.push({
                        'id': item.id,
                        'text': item.text
                    });
                }
            }, this);

            return values;
        },

        /**
         * @inheritDoc
         */
        isEmptyValue: function() {
            var value = this.getValue();

            return !value.value || value.value.length === 0;
        },

        /**
         * @inheritDoc
         */
        _getParts: function() {
            var value = _.extend({}, this.emptyValue, this.getValue());
            var dictionaryPartTemplate = this._getTemplate(this.fieldTemplateSelector);
            var parts = [];
            var selectedPartLabel = this._getSelectedChoiceLabel('choices', this.value);
            // add date parts only if embed template used
            if (this.templateTheme !== '') {
                parts.push(
                    dictionaryPartTemplate({
                        name: this.name + '_part',
                        choices: this.choices,
                        selectedChoice: value.type,
                        selectedChoiceLabel: selectedPartLabel
                    })
                );
            }

            return parts;
        },

        /**
         * Set raw value to filter
         *
         * @param value
         *
         * @return {*}
         */
        setValue: function(value) {
            var oldValue = this.value;
            this.value = tools.deepClone(value);
            this.$(this.elementSelector).inputWidget('data', this.getDataForSelect2());
            this._updateDOMValue();

            if (this.valueIsLoaded(value.value)) {
                this._onValueUpdated(this.value, oldValue);
            } else {
                this.loadValuesById('updateCriteriaLabels');
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
        },

        /**
         * @inheritDoc
         */
        _readDOMValue: function() {
            var value;
            if (this.isInitSelect2) {
                value = this.$el.find('.select-values-autocomplete').inputWidget('val');
            } else {
                value = null;
            }
            return {
                type: this._getInputValue(this.criteriaValueSelectors.type),
                value: value
            };
        },

        /**
         * @inheritDoc
         */
        _getSelectedChoiceLabel: function(property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function(choice) {
                    return (choice.value === value.type);
                });

                if (foundChoice) {
                    selectedChoiceLabel = foundChoice.label;
                }
            }

            return selectedChoiceLabel;
        },

        /**
         * @inheritDoc
         */
        _getCriteriaHint: function() {
            var value = this._getDisplayValue();
            var option = null;

            if (!_.isUndefined(value.type)) {
                var type = value.type;
                option = this._getChoiceOption(type);

                if (this.isEmptyType(type)) {
                    return option ? option.label : this.placeholder;
                }
            }

            if (!value.value || value.value.length === 0) {
                return this.placeholder;
            }

            var data = this.$(this.elementSelector).inputWidget('data');
            if (!data || !data.length) {
                data = this.previousData.length ? this.previousData : this.initialData;
            }

            if (this.valueIsLoaded(value.value)) {
                var self = this;

                var hintRawValue = _.isObject(_.first(value.value)) ?
                    _.map(value.value, _.property('text')) :
                    _.chain(value.value)
                        .map(function(id) {
                            var item =  _.find(self.selectedData, function(item) {
                                return item.id === id;
                            });

                            return item ? item.text : item;
                        })
                        .filter(_.negate(_.isUndefined))
                        .value();

                var hintValue = this.wrapHintValue ? ('"' + hintRawValue + '"') : hintRawValue;

                return (option ? option.label + ' ' : '') + hintValue;
            } else {
                return this.placeholder;
            }
        },

        /**
         * @inheritDoc
         */
        _hideCriteria: function() {
            this.$el.find(this.elementSelector).inputWidget('close');
            DictionaryFilter.__super__._hideCriteria.apply(this, arguments);
        },

        /**
         * Checking  the existence of entities with selected ids in loaded data.
         *
         * @param values
         *
         * @returns {boolean}
         */
        valueIsLoaded: function(values) {
            if (values) {
                var foundItems = 0;
                var self = this;
                _.each(values, function(item) {
                    if (self.selectedData && self.selectedData[item]) {
                        foundItems++;
                    }
                });

                return foundItems === values.length;
            }

            return true;
        }
    });

    return DictionaryFilter;
});
