define([
    'jquery',
    'routing',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    './choice-filter',
    'oroui/js/messenger'
], function($, routing, _, __, tools, ChoiceFilter, messenger) {
    'use strict';

    var DictionaryFilter;

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
            type: 'select[name="dictionary_part"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.dictionaryClass = this.filterParams.class.replace(/\\/g, '_');
            DictionaryFilter.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            this._deferredRender();
            this.loadSelectedValue();
        },

        loadSelectedValue: function() {
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
                success: function(reposne) {
                    self.value.value = reposne.results;
                    self._writeDOMValue(self.value);
                    self.renderTemplate();
                    self.applySelect2();

                    self._resolveDeferredRender();
                },
                error: function(jqXHR) {
                    messenger.showErrorMessage(__('Sorry, unexpected error was occurred'), jqXHR.responseJSON);
                }
            });
        },

        renderTemplate: function() {
            var parts = this._getParts();
            var template = _.template($(this.templateSelector).html());
            this.$el.append(template({
                parts: parts
            }));
        },

        applySelect2: function() {
            var self = this;
            var select2Config = this.getSelect2Config();
            var select2element = this.$el.find(this.elementSelector);
            var values = this.getDataForSelect2();

            select2element.removeClass('hide')
                .attr('multiple', 'multiple')
                .select2(select2Config)
                .on('change', function() {
                    self.applyValue();
                });
            select2element.select2('data',  values);

            this._criteriaRenderd = true;
        },

        getSelect2Config: function() {
            return {
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
                minimumInputLength: 0
            };
        },

        getDataForSelect2: function() {
            var values = [];
            $.each(this.value.value, function(index, value) {
                values.push({
                    'id': value.id,
                    'text': value.text
                });
            });

            return values;
        },

        isEmptyValue: function() {
            return false;
        },

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

        _writeDOMValue: function(value) {
            this._setInputValue(this.criteriaValueSelectors.type, value.type);
        },

        _readDOMValue: function() {
            return {
                type: this._getInputValue(this.criteriaValueSelectors.type),
                value: this.$el.find('.select-values-autocomplete').select2('val')
            };
        },

        _getSelectedChoiceLabel: function(property, value) {
            var selectedChoiceLabel = '';
            if (!_.isEmpty(this[property])) {
                var foundChoice = _.find(this[property], function(choice) {
                    return (choice.value === value.type);
                });
                selectedChoiceLabel = foundChoice.label;
            }

            return selectedChoiceLabel;
        }
    });

    return DictionaryFilter;
});
