define(function(require) {
    'use strict';

    var TransitionDefinitionModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    TransitionDefinitionModel = BaseModel.extend({
        defaults: {
            name: null,
            pre_conditions: null,
            conditions: null,
            post_actions: null
        },

        initialize: function() {
            if (this.get('pre_conditions') === null) {
                this.set('pre_conditions', {});
            }
            if (this.get('conditions') === null) {
                this.set('conditions', {});
            }
            if (this.get('post_actions') === null) {
                this.set('post_actions', []);
            }
        }
    });

    return TransitionDefinitionModel;
});
