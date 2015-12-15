/*global define*/
define([
    'backbone',
    'underscore',
    './collection',
    'oroui/js/items-manager/table',
    'jquery.select2'
], function(Backbone, _, ItemCollection) {
    'use strict';

    /**
     * @export  orodashboard/js/items/view
     * @class   orodashboard.items.Model
     * @extends Backbone.Model
     */
    return Backbone.View.extend({
        events: {
            'change .item-select': '_toggleButtons',
            'click .add-button:not(.disabled)': '_onAddClick',
            'click .add-all-button:not(.disabled)': '_onAddAllClick'
        },

        selectTplSelector: '#widget-items-item-select-template',
        itemTplSelector:   '#widget-items-item-template',

        requiredOptions: [
            'itemsData',
            'baseName'
        ],

        items: null,
        itemSelect: null,

        initialize: function(options) {
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.items = this._initializeItems(options.itemsData, options.baseName);

            this._initializeFilter(this.items, options);
            this._initializeItemGrid(this.items, options);
            this._toggleButtons();

            this.$dialog = this.$el.closest('.ui-dialog');
            this.$dialog.css('top', 0);
        },

        _initializeItems: function(itemsData, baseName) {
            var items = new ItemCollection(itemsData);
            items.each(function(item, index) {
                item.set('namePrefix', baseName + '[' + index + ']');
            });

            return items;
        },

        _initializeFilter: function(items, options) {
            var selectTpl = _.template(Backbone.$(this.selectTplSelector).html());
            var select = selectTpl({
                items: items
            });

            var $filterContainer = this.$('.controls');
            $filterContainer.prepend(select);
            this.itemSelect = $filterContainer.find('select');
            this.itemSelect.select2({
                allowClear: true,
                placeholder: options.placeholder || null
            });

            items.on('change:show', function(model) {
                var $option = this.itemSelect.find('option[value=' + model.id + ']');
                if (model.get('show')) {
                    $option.addClass('hide');
                } else {
                    $option.removeClass('hide');
                }
            }, this);

            var showedItems = items.where({show: true});
            _.each(showedItems, function(item) {
                var $option = this.itemSelect.find('option[value=' + item.id + ']');
                $option.addClass('hide');
            }, this);
        },

        _initializeItemGrid: function(items) {
            var $itemContainer = this.$('.item-container');
            var showedItems    = items.where({show: true});
            var filteredItems  = new ItemCollection(showedItems);

            $itemContainer.itemsManagerTable({
                itemTemplate: Backbone.$(this.itemTplSelector).html(),
                collection: filteredItems
            });

            filteredItems.on('sort add', function() {
                $itemContainer.find('input.order').each(function(index) {
                    Backbone.$(this)
                        .val(index)
                        .trigger('change');
                });
            });

            filteredItems.on('action:delete', function(model) {
                model.set('show', false);
            });

            items.on('change:show', function(model) {
                if (model.get('show')) {
                    filteredItems.add(model);
                } else {
                    filteredItems.remove(model);
                }
            });

            $itemContainer.on('change', function(e) {
                var $target = Backbone.$(e.target);
                var item = items.get($target.closest('tr').data('cid'));
                var value = $target.is(':checkbox') ? $target.is(':checked') : $target.val();
                item.set($target.data('name'), value);
            });
        },

        _onAddClick: function() {
            var item  = this.itemSelect.select2('val');
            var model = this.items.get(item);

            model.set('show', true);

            this.itemSelect.select2('val', '').change();
        },

        _onAddAllClick: function() {
            this.items.each(function(item) {
                item.set('show', true);
            });

            this.itemSelect.select2('val', '').change();
        },

        _toggleButtons: function() {
            if (this.itemSelect.select2('val')) {
                this.$('.add-button').removeClass('disabled');
            } else {
                this.$('.add-button').addClass('disabled');
            }
        }
    });
});
