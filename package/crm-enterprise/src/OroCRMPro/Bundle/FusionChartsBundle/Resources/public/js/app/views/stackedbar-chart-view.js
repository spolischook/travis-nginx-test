define(function(require) {
    'use strict';

    var StackedBarChartView;
    var _ = require('underscore');
    var DataHandler = require('orocrmprofusioncharts/js/multiple-data-handler');
    var BaseView = require('oroui/js/app/views/base/view');
    var FusionCharts = require('orocrmprofusioncharts/lib/FusionCharts');

    StackedBarChartView = BaseView.extend({
        chart: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['chartOptions']));
            StackedBarChartView.__super__.initialize.apply(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.chart.dispose();
            StackedBarChartView.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var options = this.chartOptions;
            var handler = new DataHandler(
                options.dataSource,
                options.options,
                options.isCurrencyPrepend
            );

            this.chart = new FusionCharts({
                type: 'scrollstackedcolumn2d',
                dataSource: handler.getDataSource(),
                dataFormat: 'json',
                width: '100%',
                height: 400,
                id: options.containerId,
                renderAt: this.$el.attr('id')
            });

            this.chart.render();
            return this;
        },

    });

    return StackedBarChartView;
});
