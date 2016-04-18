/*global define*/
define([
    'jquery',
    'underscore',
    'routing',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, _, routing, Backbone, __, mediator) {
    'use strict';

    return Backbone.View.extend({
        events: {
            'click': 'processClick'
        },

        /**
         * Check url
         * @property string
         */
        route: 'orocrmpro_ldap_transport_check',
        url: null,
        id: null,
        $messages: null,

        resultTemplate: _.template(
            '<div class="alert alert-<%= type %>"><%= message %></div>'
        ),

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.id = options.transportEntityId || null;
            this.url = this.getUrl();
            this.$messages = this.$el.closest('.control-group').next().find('.messages');
        },

        getUrl: function(type) {
            var params = {id: this.id};
            if (type !== undefined) {
                params.type = type;
            }

            return routing.generate(this.route, params);
        },

        /**
         * Click handler
         */
        processClick: function() {
            var data = this.$el.parents('form').serializeArray();
            var typeData = _.filter(data, function(field) {
                return field.name.indexOf('[type]') !== -1;
            });
            if (typeData.length) {
                typeData = typeData[0].value;
            }

            mediator.execute('showLoading');
            $.post(this.getUrl(typeData), data, _.bind(this.responseHandler, this), 'json')
                .always(_.bind(function(response, status) {
                    mediator.execute('hideLoading');
                    if (status !== 'success') {
                        this.renderResult('error', __('orocrmpro.ldap.transport.ldap.fields.check.message.error'));
                    }
                }, this));
        },

        responseHandler: function(data) {
            var message = '';
            var status = data.status;
            if (data.status === 'success') {
                message = __('orocrmpro.ldap.transport.ldap.fields.check.message.success');
            } else if (data.status === 'invalid') {
                message = __('orocrmpro.ldap.transport.ldap.fields.check.message.invalid');
                status = 'danger';
            }
            this.renderResult(status, message);
        },

        renderResult: function(type, message) {
            this.$messages.html((this.resultTemplate({type: type, message: message})));
        }
    });
});
