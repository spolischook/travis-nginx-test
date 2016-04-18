define([
    'oroproorganization/js/app/tools/system-access-mode-organization-provider',
    'oroimap/js/app/views/check-connection-view'
], function(systemAccessModeOrganizationProvider, BaseView) {
    'use strict';

    var ConnectionView = BaseView.extend({
        isGlobalOrg: false,

        initialize: function(options) {
            ConnectionView.__super__.initialize.apply(this, arguments);
            this.isGlobalOrg = options.isGlobalOrg;
        },

        _getUrlParams: function() {
            var params = ConnectionView.__super__._getUrlParams.apply(this, arguments);

            var organizationId = systemAccessModeOrganizationProvider.getOrganizationId();
            if (!organizationId && this.isGlobalOrg) {
                organizationId = this.organization;
            }

            if (organizationId) {
                params._sa_org_id = organizationId;
            }

            return params;
        }
    });

    return ConnectionView;
});
