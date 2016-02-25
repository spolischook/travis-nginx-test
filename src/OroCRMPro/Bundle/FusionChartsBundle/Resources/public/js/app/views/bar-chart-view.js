define(function(require) {
    'use strict';

    var BarChartView;
    var _ = require('underscore');
    var DataHandler = require('orocrmprofusioncharts/js/fusion-data-handler');
    var BaseView = require('oroui/js/app/views/base/view');
    var FusionCharts = require('orocrmprofusioncharts/lib/FusionCharts');

    BarChartView = BaseView.extend({
        chart: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['chartOptions']));
            BarChartView.__super__.initialize.apply(this, options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var dataSource = this.prepareDataSource();
            this.chart = new FusionCharts({
                type: 'Column3D',
                dataSource: {
                    'chart': dataSource.chart,
                    'trendlines': dataSource.trendlines,
                    'data': []
                },
                dataFormat: 'json',
                width: '100%',
                height: 300,
                id: this.chartOptions.containerId,
                renderAt: this.$el.attr('id')
            });

            this.chart.setJSONData(dataSource);
            this.chart.render();
            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.chart.dispose();
            BarChartView.__super__.dispose.call(this);
        },

        prepareDataSource: function() {
            var options = this.chartOptions;
            var handler = new DataHandler(options.dataSource, options.schema, options.isCurrencyPrepend);
            var dataSource = handler.getDataSource();
            var max = Math.ceil(handler.getMaxValue() * 1.2); // left space to label with value above
            var precision = Math.pow(10, Math.floor(Math.log10(max / options.averageLineQuantity)));
            var step = this._mround(max / options.averageLineQuantity, precision);
            var roundMax = this._mround(max, step);
            dataSource.chart.yAxisMaxValue = roundMax;
            dataSource.chart.numDivLines = Math.floor(roundMax / step) - 1;
            return dataSource;
        },

        /**
         * Returns a number rounded to the desired multiple.
         *
         * @param number
         * @param multiple
         * @returns {number}
         * @private
         */
        _mround: function(number, multiple) {
            return Math.round(number / multiple) * multiple;
        }
    });

    return BarChartView;
});
