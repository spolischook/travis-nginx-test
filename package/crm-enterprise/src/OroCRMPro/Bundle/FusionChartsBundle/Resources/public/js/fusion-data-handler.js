define(['orochart/js/data_formatter', 'orolocale/js/locale-settings', 'underscore', 'orotranslation/js/translator'],
    function(dataFormatter, localeSettings, _, __) {
        'use strict';

        /**
         * @export orocrmprofusioncharts/js/fusion-data-handler
         * @name   dataHandler
         */
        var FusionDataHandler;
        FusionDataHandler = function(dataSource, schema, isCurrencyPrepend) {

            /**
             * Order data labels(fusion chart not ordered labels)
             */
            dataSource.data = dataSource.data.sort(function(first, second) {
                if (first.label === null || first.label === undefined) {
                    return -1;
                }
                if (second.label === null || second.label === undefined) {
                    return 1;
                }
                var firstLabel = dataFormatter.parseValue(first.label, schema.label.type);
                var secondLabel = dataFormatter.parseValue(second.label, schema.label.type);
                if (firstLabel === secondLabel) {
                    return 0;
                }
                return firstLabel > secondLabel ? 1 : -1;
            });

            if (schema.value.type === 'percent') {
                dataSource.chart.numberSuffix = '%';
            } else if (schema.value.type === 'currency') {
                if (isCurrencyPrepend !== null) {
                    var currencySymbol = localeSettings.getCurrencySymbol();
                    var symbolPosition = 'number' + (isCurrencyPrepend ? 'Prefix' : 'Suffix');
                    dataSource.chart[symbolPosition] = currencySymbol;
                }
                dataSource.chart.forceDecimals = '1';
            }

            var max = 0;
            if (dataSource.data.length) {
                max = dataFormatter.parseValue(dataSource.data[0].value, schema.value.type);
            }
            var min = max;

            for (var i in dataSource.data) {
                if (!dataSource.data.hasOwnProperty(i)) {
                    continue;
                }
                var point = dataSource.data[i];
                if (point.label !== null && point.label !== undefined) {
                    var labelValue = dataFormatter.parseValue(point.label, schema.label.type);
                    point.label = labelValue === null ?
                        point.label : dataFormatter.formatValue(labelValue, schema.label.type);
                } else {
                    point.label = __('oro.chart.no_data');
                }

                point.displayValue = dataFormatter.formatValue(point.value, schema.value.type);
                if (schema.value.type === 'percent') {
                    point.value *= 100;
                } else {
                    point.value = point.value === null ? 0 : dataFormatter.parseValue(point.value, schema.value.type);
                }

                if (point.value > max) {
                    max = point.value;
                }
                if (point.value < min) {
                    min = point.value;
                }

                dataSource.data[i] = point;
                dataSource.chart.yAxisValuesStep = 1;
            }
            this.dataSource = dataSource;
            this.min = min;
            this.max = max;
        };

        _.extend(FusionDataHandler.prototype, {
            /**
             * @return {int|float}
             */
            getMaxValue: function() {
                return this.max;
            },

            /**
             * @return {int|float}
             */
            getMinValue: function() {
                return this.min;
            },

            /**
             * @return {object}
             */
            getDataSource: function() {
                return this.dataSource;
            }
        });
        return FusionDataHandler;
    }
);
