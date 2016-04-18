define(function(require) {
    'use strict';

    var Select2ShareView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    Select2ShareView = BaseView.extend({
        events: {
            'select2-selecting': 'onSelect'
        },

        initialize: function() {
            Select2ShareView.__super__.initialize.apply(this, arguments);
            this.$el.one('select2-focus', _.bind(function() {
                this.$el.select2('open');
            }, this));
        },

        onSelect: function(e) {
            e.stopPropagation();
            mediator.trigger('datagrid:shared-datagrid:add:data-from-select2', e.object);
            $(e.currentTarget).select2('close');
            return false;
        }
    });

    return Select2ShareView;
});
