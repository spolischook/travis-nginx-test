define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'orolocale/js/formatter/address'
], function(_, Backbone, __, mediator, addressFormatter) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroaddress/js/address/view
     * @class   oroaddress.address.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'div',

        attributes: {
            'class': 'list-item map-item'
        },

        events: {
            'click': 'activate',
            'click .item-edit-button': 'edit',
            'click .item-remove-button': 'close'
        },

        initialize: function() {
            this.$el.attr('id', 'address-book-' + this.model.id);
            this.template = _.template($('#template-addressbook-item').html());
            this.listenTo(this.model, 'destroy', this.remove);
            this.listenTo(this.model, 'change:active', this.toggleActive);
        },

        activate: function() {
            this.model.set('active', true);
        },

        toggleActive: function() {
            if (this.model.get('active')) {
                this.$el.addClass('active');
            } else {
                this.$el.removeClass('active');
            }
        },

        edit: function() {
            this.trigger('edit', this, this.model);
        },

        close: function() {
            if (this.model.get('primary')) {
                mediator.execute('showErrorMessage', __('Primary address can not be removed'));
            } else {
                this.model.destroy({wait: true});
            }
        },

        render: function() {
            var data = this.model.toJSON();
            data.formatted_address = addressFormatter.format({
                prefix: data.namePrefix,
                suffix: data.nameSuffix,
                first_name: data.firstName,
                middle_name: data.middleName,
                last_name: data.lastName,
                organization: data.organization,
                street: data.street,
                street2: data.street2,
                city: data.city,
                country: data.country,
                country_iso2: data.countryIso2,
                country_iso3: data.countryIso3,
                postal_code: data.postalCode,
                region: data.region || data.regionText,
                region_code: data.regionCode
            }, null, '\n');
            this.$el.append(this.template(data));
            if (this.model.get('primary')) {
                this.activate();
            }
            return this;
        }
    });
});
