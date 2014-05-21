/*global define*/
define(['orochart/js/data_formatter', 'orolocale/js/locale-settings', 'orotranslation/js/translator'],
    function (dataFormatter, localeSettings, __) {
        'use strict';

        /**
         * @export orocrmprofusioncharts/js/fusion-data-handler
         * @name   dataHandler
         */
        return function(dataSource, schema, isCurrencyPrepend){
            this.dataSource = dataSource;
            /**
             * Order data labels(fusion chart not ordered labels)
             */
            this.dataSource.data = this.dataSource.data.sort(function(first, second){
                if (first.label == null) {
                    return -1;
                }
                if (second.label == null) {
                    return 1;
                }
                var firstLabel = dataFormatter.parseValue(first.label, schema.label.type);
                var secondLabel = dataFormatter.parseValue(second.label, schema.label.type);
                if (firstLabel == secondLabel) {
                    return 0;
                }
                return firstLabel > secondLabel ? 1 : -1;
            });

            if (schema.value.type == 'percent') {
                this.dataSource.chart['numberSuffix'] = '%';
            } else if (schema.value.type == 'currency') {
                var currencySymbol = localeSettings.getCurrencySymbol();
                var symbolPosition = 'number' + (isCurrencyPrepend ? 'Prefix' : 'Suffix');
                this.dataSource.chart[symbolPosition] = currencySymbol;
                this.dataSource.chart['forceDecimals'] = '1';
            }

            var max = dataFormatter.parseValue(this.dataSource.data[0].value, schema.value.type);
            var min = max;
            for (var i in this.dataSource.data) {
                var point = this.dataSource.data[i];
                if (point.label != null) {
                    var labelValue = dataFormatter.parseValue(point.label, schema.label.type);
                    point.label = labelValue === null ? point.label : dataFormatter.formatValue(labelValue, schema.label.type);
                } else {
                    point.label = __('oro.chart.no_data');
                }

                point.displayValue = dataFormatter.formatValue(point.value, schema.value.type);
                if (schema.value.type == 'percent') {
                    point.value *= 100;
                } else {
                    point.value = point.value === null ? 0 :dataFormatter.parseValue(point.value, schema.value.type);
                }

                if (point.value > max) {
                    max = point.value;
                }
                if (point.value < min) {
                    min = point.value;
                }

                this.dataSource.data[i] = point;
            }

            /**
             * @return {int|float}
             */
            this.getMaxValue = function(){
                return max;
            };

            /**
             * @return {int|float}
             */
            this.getMinValue = function(){
                return min;
            };

            /**
             * @return {object}
             */
            this.getDataSource = function(){
                return this.dataSource;
            }
        }
    }
);
