define([
    'backbone',
    'underscore',
    'routing',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/messenger'
], function(Backbone, _, routing, __, mediator, messenger) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/translation/translations/config-action
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /**
         * Status constants could be overridden if passed to constructor
         * {@see Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface }
         *
         * @property {}
         */
        options: {
            STATUS_NEW: 1,
            STATUS_DOWNLOADED: 2,
            STATUS_ENABLED: 3
        },

        route: 'oro_translation_download',

        buttonsSelector: '.available-translation-widget-container .btn',

        buttonTemplate: _.template('<button class="btn btn-mini" data-lang="<%= code %>" ' +
            'data-action="<%= action %>"><%= label %></button>'),

        /**
         * Constructor
         */
        initialize: function() {
            if (this.el.tagName !== 'INPUT') {
                throw new TypeError('Configuration field el should be set');
            }

            $(this.buttonsSelector).on('click', _.bind(this.clickHandler, this));
        },

        /**
         * On click event handler
         *
         * @param {jQuery.Event} e
         */
        clickHandler: function(e) {
            e.preventDefault();

            var $el = $(e.currentTarget);
            var action = $el.data('action');
            var code = $el.data('lang');

            if (_.isUndefined(action)) {
                throw new TypeError('Attribute "data-action" should be set for action button');
            } else if (_.indexOf(['enable', 'disable', 'download', 'update'], action) === -1) {
                throw new TypeError('Unknown action');
            }

            if (_.isUndefined(code)) {
                throw new TypeError('Attribute "data-lang" should be set for action button');
            }

            var actionMediator = {
                el: $el,
                action: action,
                code: code
            };
            this.performAction(actionMediator);
        },

        /**
         * Perform action for current button
         *
         * @param {Object} actionMediator
         */
        performAction: function(actionMediator) {
            if (actionMediator.action === 'download' || actionMediator.action === 'update') {
                mediator.execute('showLoading');

                var url = routing.generate(this.route, {code: actionMediator.code});
                $.getJSON(url, _.bind(function(response) {
                        if (true === response.success) {
                            this.markAsUpToDate(actionMediator);
                            this.postAction(actionMediator);
                        }
                    }, this))
                    .always(_.bind(function(response, status) {
                        var message;

                        mediator.execute('hideLoading');

                        if (status !== 'success' || response.success !== true) {
                            response = response.responseJSON ? response.responseJSON : (response || {});
                            message = _.isUndefined(response.message) ? __('unknown') : __(response.message);
                            message = __('Could not download translations, error: ') + message;
                        } else {
                            message = actionMediator.action === 'download' ?
                                __('Download finished.')
                                : __('Update finished.');
                        }

                        messenger.notificationFlashMessage(status !== 'success' ? 'error' : 'success', message);
                    }, this));
            } else {
                this.postAction(actionMediator);
            }

        },

        /**
         * Post action callback
         *
         * @param {Object} actionMediator
         */
        postAction: function(actionMediator) {
            var $newButton;
            var action = actionMediator.action;
            var code = actionMediator.code;
            var value = this.$el.val();
            var config = JSON.parse(value ? value : '{}');

            if (action === 'download' || action === 'disable') {
                $newButton = $(this.buttonTemplate({code: code, action: 'enable', label: __('Enable')}));
                config[code] = this.options.STATUS_DOWNLOADED;
            } else if (action === 'enable') {
                $newButton = $(this.buttonTemplate({code: code, action: 'disable', label: __('Disable')}));
                config[code] = this.options.STATUS_ENABLED;
            }

            $($newButton).on('click', _.bind(this.clickHandler, this));
            actionMediator.el.replaceWith($newButton);

            this.$el.val(JSON.stringify(config));

            if (action === 'enable' || action === 'disable') {
                this.$el.parents('form').submit();
            }
        },

        /**
         * Mark given row as up to date
         */
        markAsUpToDate: function(actionMediator) {
            var tableLine = actionMediator.el.parents('tr');

            if (actionMediator.action === 'update') {
                // remove update button
                actionMediator.el.remove();
            }
            tableLine.find('.translation-status').html($('<span class="status-up-to-date">' +
                __('Up to date') + '</span>'));
        }
    });
});
