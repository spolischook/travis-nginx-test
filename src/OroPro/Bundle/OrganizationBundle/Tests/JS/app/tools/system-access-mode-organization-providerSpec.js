define([
    'oroproorganization/js/app/tools/system-access-mode-organization-provider'
], function(systemAccessModeOrganizationProvider) {
    'use strict';

    var data = [
        {
            url: 'http://host.com/config/mailbox/create?' +
                'redirectData%5Bparameters%5D%5BactiveGroup%5D=platform&' +
                'redirectData%5Bparameters%5D%5BactiveSubGroup%5D=email_configuration&' +
                'redirectData%5Broute%5D=oro_config_configuration_system&form%5B_sa_org_id%5D=1',
            expectedResult: '1'
        },
        {
            url: 'http://host.com/config/mailbox/create?' +
                'redirectData%5Bparameters%5D%5BactiveGroup%5D=platform&' +
                'redirectData%5Bparameters%5D%5BactiveSubGroup%5D=email_configuration&' +
                'redirectData%5Broute%5D=oro_config_configuration_system&form%5B_sa_org_id%5D=2',
            expectedResult: '2'
        },
        {
            url: 'http://host.com/config/mailbox/create?' +
                'redirectData%5Broute%5D=oro_config_configuration_system&' +
                'redirectData%5Bparameters%5D%5BactiveGroup%5D=platform&' +
                'redirectData%5Bparameters%5D%5BactiveSubGroup%5D=email_configuration',
            expectedResult: undefined
        }
    ];

    describe('oroproorganization/js/app/tools/system-access-mode-organization-provider', function() {
        it('should return organization id', function() {
            var originalProperty = systemAccessModeOrganizationProvider._getCurrentUrl;
            var currentUrl = null;
            systemAccessModeOrganizationProvider._getCurrentUrl = function() {
                return currentUrl;
            };

            data.forEach(function(args) {
                currentUrl = args.url;
                expect(systemAccessModeOrganizationProvider.getOrganizationId()).toEqual(args.expectedResult);
            });

            systemAccessModeOrganizationProvider._getCurrentUrl = originalProperty;
        });
    });
});
