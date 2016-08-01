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
            BarChartView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var dataSource = this.prepareDataSource();
            this.chart = new FusionCharts({
                type: dataSource.type,
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
            var lineStep;
            var topLineValue;
            var options = this.chartOptions;
            var handler = new DataHandler(options.dataSource, options.schema, options.isCurrencyPrepend);
            var dataSource = handler.getDataSource();
            var maxValue = Math.ceil(handler.getMaxValue() * 1.12); // left space to label with value above
            var exponent = Math.floor(Math.log10(maxValue));
            var multiplier = Math.pow(10, exponent);
            var significand = maxValue / multiplier;
            if (significand < 2) {
                lineStep = multiplier / 5;
            } else if (significand < 4) {
                lineStep = multiplier / 2;
            } else if (significand < 8) {
                lineStep = multiplier;
            } else {
                lineStep = 2 * multiplier;
            }
            topLineValue = Math.ceil(maxValue / lineStep) * lineStep;
            dataSource.chart.yAxisMaxValue = topLineValue;
            dataSource.chart.numDivLines = topLineValue / lineStep - 1;
            return dataSource;
        }
    });

    return BarChartView;
});
