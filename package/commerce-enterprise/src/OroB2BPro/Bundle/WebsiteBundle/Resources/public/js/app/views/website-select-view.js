define(function (require) {
    'use strict';

    var WebsiteSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    WebsiteSelectView = BaseView.extend({
        $select: {},

        options: {
            selectors: {
                website: 'select[name$="[website]"]'
            }
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.$select = $(this.options.el).find(this.options.selectors.website);

            this.$select.on('change', this._triggerUpdateTotals);
            mediator.on('pricing:refresh:products-tier-prices:before', this._updateContext, this);
            mediator.on('pricing:refresh:line-items-matched-prices:before', this._updateContext, this);
        },

        _updateContext: function(context){
            context.requestAttributes['websiteId'] = this.$select.val();
        },

        _triggerUpdateTotals: function () {
            mediator.trigger('update:totals');
            mediator.trigger('pricing:load:prices');
            mediator.trigger('entry-point:order:trigger');
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.$select.off('change', this._triggerUpdateTotals);
            mediator.off('pricing:refresh:products-tier-prices:before', this._updateContext, this);
            mediator.off('pricing:refresh:line-items-matched-prices:before', this._updateContext, this);
        }
    });

    return WebsiteSelectView;
});
