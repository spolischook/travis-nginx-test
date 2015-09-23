define(function(require) {
    'use strict';

    var _ = require('underscore');
    var FusionCharts = require('orocrmprofusioncharts/lib/FusionCharts');
    require('orocrmprofusioncharts/lib/FusionCharts.HC');

    FusionCharts(['private', 'OroCrmPro', function() {
        var SmartLabelManager;
        if (!this.hcLib || !_.isFunction(this.hcLib.SmartLabelManager)) {
            return;
        }

        this.hcLib.Raphael._url = '';
        SmartLabelManager = this.hcLib.SmartLabelManager;
        SmartLabelManager.prototype.dispose = _.wrap(SmartLabelManager.prototype.dispose, function(dispose) {
            var parent;
            var container = this.parentContainer;
            dispose.apply(this, arguments);
            if (container && (parent = container.parentNode)) {
                parent.removeChild(container);
            }
        });
    }]);

    return FusionCharts;
});
