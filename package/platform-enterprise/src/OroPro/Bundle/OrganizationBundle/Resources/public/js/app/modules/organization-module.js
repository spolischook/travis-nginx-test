define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init OrganizationSwitchView
     */
    BaseController.loadBeforeAction([
        'oroproorganization/js/app/views/organization-switch-view'
    ], function(OrganizationSwitchView) {
        BaseController.addToReuse('organization_switch', OrganizationSwitchView, {
            el: '#organization-switcher'
        });
    });
});
