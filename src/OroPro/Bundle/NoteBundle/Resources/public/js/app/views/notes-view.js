/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var NotesView,
        OriginNotesView = require('oronote/js/app/views/notes-view');

    NotesView = OriginNotesView.extend({

        /**
         * Fetches url for certain action
         *
         * @param {string} actionKey
         * @param {Backbone.Model=}model
         * @returns {string}
         * @protected
         */
        _getUrl: function (actionKey, model) {
            var url = '';

            if (_.isFunction(this.options.urls[actionKey])) {
                url =  this.options.urls[actionKey](model);
            } else {
                url = this.options.urls[actionKey];
            }

            var _sa_org_id = $('input#_sa_org_id').val();
            if (_sa_org_id) {
                url = url + (url.indexOf('?') == -1 ? '?' : '&') + '_sa_org_id=' + _sa_org_id;
            }

            return url;
        }
    });

    return NotesView;
});
