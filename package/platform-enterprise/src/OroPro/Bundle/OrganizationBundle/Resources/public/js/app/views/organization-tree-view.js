define(['oro/select2-bu-tree-autocomplete-component', 'require'], function(dependence, require) {
    'use strict';

    var OrganizationTreeView;

    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');

    OrganizationTreeView = BaseView.extend({
        events: {
            'change [data-name="organization"]': 'onChangeOrganization'
        },

        initialize: function(options) {
            OrganizationTreeView.__super__.initialize.apply(this, arguments);
            mediator.trigger('initOrganization', JSON.parse(this.$el.find('input[type="hidden"]').val()));
        },

        onChangeOrganization: function(e) {
            mediator.trigger('changed:selectedOrganization', {
                organizationId: $(e.target).attr('value'),
                add: e.target.checked
            });

            var selectedOrganizations = this.$el
                .find('input[name="oro_user_user_form[organizations][organizations][]"]');
            var value = selectedOrganizations.val();
            var organizaitionId = parseInt($(e.target).attr('value'));

            value = JSON.parse(value);
            if (e.target.checked) {
                value.organizations.push(organizaitionId);
            } else {
                var index = value.organizations.indexOf(organizaitionId);
                if (index !== -1) {
                    value.organizations.splice(index, 1);
                }
            }
            selectedOrganizations.val(JSON.stringify(value));
        }
    });

    return OrganizationTreeView;
});
