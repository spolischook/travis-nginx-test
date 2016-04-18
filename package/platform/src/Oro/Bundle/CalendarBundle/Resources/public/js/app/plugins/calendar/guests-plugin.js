define(function(require) {
    'use strict';

    var GuestsPlugin;
    var $ = require('jquery');
    var _ = require('underscore');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var GuestNotifierView = require('orocalendar/js/app/views/guest-notifier-view');

    GuestsPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'event:added', this.onEventAdded);
            this.listenTo(this.main, 'event:changed', this.onEventChanged);
            this.listenTo(this.main, 'event:deleted', this.onEventDeleted);
            this.listenTo(this.main, 'event:beforeSave', this.onEventBeforeSave);
            GuestsPlugin.__super__.enable.call(this);
        },

        // no disable() function 'cause attached callbacks will be removed in parent disable method

        /**
         * Verifies if event is a guest event
         *
         * @param eventModel
         * @returns {boolean}
         */
        hasParentEvent: function(eventModel) {
            var result = false;
            var parentEventId = eventModel.get('parentEventId');
            var alias = eventModel.get('calendarAlias');
            if (parentEventId) {
                result = Boolean(this.main.getConnectionCollection().find(function(c) {
                    return c.get('calendarAlias') === alias &&
                        this.collection.get(c.get('calendarUid') + '_' + parentEventId);
                }, this));
            }
            return result;
        },

        /**
         * Verifies if event has a loaded guest events
         *
         * @param parentEventModel
         * @returns {boolean}
         */
        hasLoadedGuestEvents: function(parentEventModel) {
            var result = false;
            var guests = parentEventModel.get('invitedUsers');
            guests = _.isNull(guests) ? [] : guests;
            if (parentEventModel.hasChanged('invitedUsers') && !_.isEmpty(parentEventModel.previous('invitedUsers'))) {
                guests = _.union(guests, parentEventModel.previous('invitedUsers'));
            }
            if (!_.isEmpty(guests)) {
                result = Boolean(this.main.getConnectionCollection().find(function(connection) {
                    return -1 !== guests.indexOf(connection.get('userId'));
                }, this));
            }
            return result;
        },

        /**
         * Returns linked guest events
         *
         * @param parentEventModel
         * @returns {Array.<EventModel>}
         */
        findGuestEventModels: function(parentEventModel) {
            return this.main.collection.where({
                parentEventId: '' + parentEventModel.originalId
            });
        },

        /**
         * "event:added" callback
         *
         * @param eventModel
         */
        onEventAdded: function(eventModel) {
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasLoadedGuestEvents(eventModel)) {
                this.main.updateEvents();
            }
        },

        /**
         * "event:changed" callback
         *
         * @param eventModel
         */
        onEventChanged: function(eventModel) {
            var guestEventModels;
            var i;
            var updatedAttrs;
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasLoadedGuestEvents(eventModel)) {
                if (eventModel.hasChanged('invitedUsers')) {
                    eventModel.once('sync', this.main.updateEvents, this.main);
                    return;
                }
                // update linked events
                guestEventModels = this.findGuestEventModels(eventModel);
                updatedAttrs = _.pick(eventModel.changedAttributes(),
                    ['start', 'end', 'allDay', 'title', 'description']);
                for (i = 0; i < guestEventModels.length; i++) {
                    // fill with updated attributes in parent
                    guestEventModels[i].set(updatedAttrs);
                }
            }
        },

        /**
         * "event:deleted" callback
         *
         * @param eventModel
         */
        onEventDeleted: function(eventModel) {
            var guestEventModels;
            var i;
            if (this.hasLoadedGuestEvents(eventModel)) {
                // remove guests
                guestEventModels = this.findGuestEventModels(eventModel);
                for (i = 0; i < guestEventModels.length; i++) {
                    this.main.getCalendarElement().fullCalendar('removeEvents', guestEventModels[i].id);
                    this.main.collection.remove(guestEventModels[i]);
                    guestEventModels[i].dispose();
                }
            }
        },

        /**
         * "event:beforeSave" callback.
         *
         * @param eventModel
         * @param {Array.<$.promise>} promises script will wait execution of all promises before save
         * @param {object} attrs to be set on event model
         */
        onEventBeforeSave: function(eventModel, promises, attrs) {
            if (this.hasLoadedGuestEvents(eventModel)) {
                var cleanUp;
                var deferredConfirmation = $.Deferred();
                promises.push(deferredConfirmation);

                if (!this.modal) {
                    cleanUp = _.bind(function() {
                        this.modal.dispose();
                        delete this.modal;
                    }, this);

                    this.modal = GuestNotifierView.createConfirmNotificationDialog();

                    this.modal.on('ok', _.bind(function() {
                        attrs.notifyInvitedUsers = true;
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.on('cancel', _.bind(function() {
                        attrs.notifyInvitedUsers = false;
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.on('close', _.bind(function() {
                        deferredConfirmation.reject();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.open();
                }
            }
        }
    });

    return GuestsPlugin;
});
