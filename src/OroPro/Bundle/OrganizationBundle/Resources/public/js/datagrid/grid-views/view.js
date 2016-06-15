define(function(require) {
    'use strict';

    var _ = require('underscore');
    var routing = require('routing');

    var ProGridViewsView;
    var GridViewsView = require('orodatagrid/js/datagrid/grid-views/view');

    ProGridViewsView = GridViewsView.extend({
        /** @property */
        saOrgId: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProGridViewsView.__super__.initialize.call(this, options);

            if (!_.isUndefined(options._sa_org_id)) {
                this.saOrgId = options._sa_org_id;
            }
        },

        /**
         * @inheritDoc
         */
        _createViewModel: function(data) {
            var model = ProGridViewsView.__super__._createViewModel.call(this, data);

            if (this.saOrgId) {
                var saOrgId = this.saOrgId;
                model.urlRoot = function() {
                    var urlParams = {};
                    if (this.isNew()) {
                        urlParams._sa_org_id = saOrgId;
                    }

                    return routing.generate(this.route, urlParams);
                };
            }

            return model;
        }
    });

    return ProGridViewsView;
});
