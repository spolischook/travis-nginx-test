define(function(require) {
    'use strict';

    var InviteModalView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');
    var StartButtonView = require('./start-button-view');
    var modalContentTemplate = require('tpl!../../../templates/invite-modal-content.html');

    InviteModalView = Modal.extend({
        className: 'modal oro-modal-normal invite-hangout-modal',

        /**
         * @type {Object}
         */
        hangoutOptions: null,

        initialize: function(options) {
            _.extend(this, _.defaults(_.pick(options, ['hangoutOptions']), {
                hangoutOptions: {}
            }));

            InviteModalView.__super__.initialize.call(this, options);
        },

        getContentTemplateData: function() {
            return {
                invites: this.hangoutOptions.invites || []
            };
        },

        render: function() {
            this.options.title = __('orocrm.hangoutscall.start_hangouts_dialog.title');
            this.options.content = modalContentTemplate(this.getContentTemplateData());

            InviteModalView.__super__.render.call(this);

            this.startButtonView = new StartButtonView({
                autoRender: true,
                hangoutOptions: this.hangoutOptions
            });

            if (this.startButtonView.deferredRender) {
                this.startButtonView.deferredRender.fail(_.bind(function() {
                    this.trigger('fail');
                }, this));
            }

            this.$('.modal-footer').append(this.startButtonView.$el);

            return this;
        },

        remove: function() {
            if (this.startButtonView) {
                this.startButtonView.dispose();
                delete this.startButtonView;
            }
            InviteModalView.__super__.remove.call(this);
        }
    });

    return InviteModalView;
});
