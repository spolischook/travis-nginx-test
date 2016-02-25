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
            var step;
            var roundMax;
            var options = this.chartOptions;
            var handler = new DataHandler(options.dataSource, options.schema, options.isCurrencyPrepend);
            var dataSource = handler.getDataSource();
            var max = Math.ceil(handler.getMaxValue() * 1.12); // left space to label with value above
            var precision = Math.pow(10, Math.floor(Math.log10(max)));
            if (max / precision < 1) {
                step = 1;
            } else if (max / precision < 2) {
                step = 2;
            } else if (max / precision < 4) {
                step = 5;
            } else if (max / precision < 8) {
                step = 10;
            } else {
                step = 20;
            }
            step *= precision / 10;
            roundMax = Math.ceil(max / step) * step;
            dataSource.chart.yAxisMaxValue = roundMax;
            dataSource.chart.numDivLines = roundMax / step - 1;
            return dataSource;
        }
    });

    return BarChartView;
});
