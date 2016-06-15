define([
    'oroproorganization/js/app/tools/system-access-mode-organization-provider',
    'oroimap/js/app/components/account-type-component'
], function(systemAccessModeOrganizationProvider, BaseComponent) {
    'use strict';

    var AccountTypeComponent;

    AccountTypeComponent = BaseComponent.extend({
        isGlobalOrg: false,

        /**
         * @param options
         */
        initialize: function(options) {
            AccountTypeComponent.__super__.initialize.apply(this, arguments);
            this.isGlobalOrg = options.viewOptions.isGlobalOrg;
            this.organization = options.organization;
        },

        /**
         * Prepare parameters for getUrl method
         * @returns {*|{}}
         * @private
         */
        _getUrlParams: function() {
            var params = AccountTypeComponent.__super__._getUrlParams.apply(this, arguments);

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

    return AccountTypeComponent;
});
