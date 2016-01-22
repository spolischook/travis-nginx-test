define([
    'oroproorganization/js/app/tools/system-access-mode-organization-provider',
    'oroimap/js/app/components/imap-gmail-component'
], function(systemAccessModeOrganizationProvider, BaseComponent) {
    'use strict';

    var ImapGmailComponent;

    ImapGmailComponent = BaseComponent.extend({
        isGlobalOrg: false,

        /**
         * @param options
         */
        initialize: function(options) {
            ImapGmailComponent.__super__.initialize.apply(this, arguments);
            this.isGlobalOrg = options.viewOptions.isGlobalOrg;
            this.organization = options.organization;
        },

        /**
         * Prepare parameters for getUrl method
         * @returns {*|{}}
         * @private
         */
        _getUrlParams: function() {
            var params = ImapGmailComponent.__super__._getUrlParams.apply(this, arguments);

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

    return ImapGmailComponent;
});
