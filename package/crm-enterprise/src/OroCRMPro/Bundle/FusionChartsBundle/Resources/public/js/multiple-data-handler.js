define([
    'underscore',
    'orochart/js/data_formatter',
    'orolocale/js/locale-settings',
    'orotranslation/js/translator'
], function(_, dataFormatter, localeSettings, __) {
    'use strict';

    /**
     * @export orocrmprofusioncharts/js/multiple-data-handler
     * @name   multipleHandler
     */
    return function(dataSource, options, isCurrencyPrepend) {
        if (options.data_schema.value.type === 'percent') {
            dataSource.chart.numberSuffix = '%';
        } else if (options.data_schema.value.type === 'currency') {
            if (isCurrencyPrepend !== null) {
                var currencySymbol = localeSettings.getCurrencySymbol();
                var symbolPosition = 'number' + (isCurrencyPrepend ? 'Prefix' : 'Suffix');
                dataSource.chart[symbolPosition] = currencySymbol;
            }
            dataSource.chart.forceDecimals = '1';
        }

        var max = 0;
        var min = 0;

        _.each(dataSource.dataset, function(dataset) {
            var prev = 0;
            _.each(dataset.data, function(data) {
                data.displayValue = dataFormatter.formatValue(data.value, options.data_schema.value.type);
                if (options.data_schema.value.type === 'percent') {
                    data.value *= 100;
                } else {
                    data.value = data.value === null ?
                        0 : dataFormatter.parseValue(data.value, options.data_schema.value.type);
                }

                if (options.default_settings && options.default_settings.trackTotal) {
                    data.value += prev;
                    data.displayValue = data.value;
                }

                max = _.max([
                    max,
                    data.value,
                    dataFormatter.parseValue(data.value, options.data_schema.value.type)
                ]);

                min = _.min([data.value, min]);

                prev = data.value;
            });
        });

        _.each(dataSource.categories.category, function(category) {
            if (category.label !== null && category.label !== void 0) {
                var labelValue = dataFormatter.parseValue(category.label, options.data_schema.label.type);
                category.label = labelValue === null ?
                    category.label : dataFormatter.formatValue(labelValue, options.data_schema.label.type);
            } else {
                category.label = __('oro.chart.no_data');
            }
        });

        /**
         * @return {object}
         */
        this.getDataSource = function() {
            return dataSource;
        };
    };
});
