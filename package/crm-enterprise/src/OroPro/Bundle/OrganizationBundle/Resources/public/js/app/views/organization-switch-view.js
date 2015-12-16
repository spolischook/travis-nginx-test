define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var OrganizationSwitchView;

    OrganizationSwitchView = PageRegionView.extend({
        template: function(data) {
            return data.organization_switch;
        },
        pageItems: ['organization_switch']
    });

    return OrganizationSwitchView;
});
