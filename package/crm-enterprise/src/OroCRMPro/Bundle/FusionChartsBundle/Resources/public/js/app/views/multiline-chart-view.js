define(function(require) {
    'use strict';

    var MultilineChartView;
    var _ = require('underscore');
    var MultipleHandler = require('orocrmprofusioncharts/js/multiple-data-handler');
    var BaseView = require('oroui/js/app/views/base/view');
    require('orocrmprofusioncharts/lib/FusionCharts.jqueryplugin');

    MultilineChartView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['chartOptions']));
            MultilineChartView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.disposeFusionCharts();
            MultilineChartView.__super__.dispose.call(this);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var handler;
            var options = this.chartOptions;

            handler = new MultipleHandler(
                options.dataSource,
                options.options,
                options.isCurrencyPrepend
            );

            this.$el.insertFusionCharts({
                type: 'MSLine',
                dataSource: handler.getDataSource(),
                dataFormat: 'json',
                width: '100%',
                height: 400,
                id: options.containerId
            });

            return this;
        }
    });

    return MultilineChartView;
});
