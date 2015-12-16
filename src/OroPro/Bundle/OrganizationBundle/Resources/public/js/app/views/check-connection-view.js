define([
    'oroproorganization/js/app/tools/system-access-mode-organization-provider',
    'oroimap/js/app/views/check-connection-view'
], function(systemAccessModeOrganizationProvider, BaseView) {
    'use strict';

    var ConnectionView = BaseView.extend({
        _getUrlParams: function() {
            var params = ConnectionView.__super__._getUrlParams.apply(this, arguments);

            var organizationId = systemAccessModeOrganizationProvider.getOrganizationId();
            if (organizationId) {
                params._sa_org_id = organizationId;
            }

            return params;
        }
    });

    return ConnectionView;
});
