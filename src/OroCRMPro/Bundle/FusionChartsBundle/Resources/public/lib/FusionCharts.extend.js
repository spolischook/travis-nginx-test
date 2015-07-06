define(function (require) {
    var _ = require('underscore');
        FusionCharts = require('orocrmprofusioncharts/lib/FusionCharts');
    require('orocrmprofusioncharts/lib/FusionCharts.HC');

    FusionCharts(["private", "OroCrmPro", function () {
        var SmartLabelManager;
        if (!this.hcLib || !_.isFunction(this.hcLib.SmartLabelManager)) {
            return;
        }

        SmartLabelManager = this.hcLib.SmartLabelManager;
        SmartLabelManager.prototype.dispose = _.wrap(SmartLabelManager.prototype.dispose, function (dispose) {
            var parent,
                container = this.parentContainer;
            dispose.apply(this, arguments);
            if (container && (parent = container.parentNode)) {
                parent.removeChild(container);
            }
        });
    }]);

    return FusionCharts;
});
