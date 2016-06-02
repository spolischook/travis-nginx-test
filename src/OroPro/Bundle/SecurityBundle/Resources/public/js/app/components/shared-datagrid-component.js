define(function(require) {
    'use strict';

    var SharedDatagridComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var helper = require('oroprosecurity/js/app/helper/component-helper');

    /**
     * @exports SharedDatagridComponent
     */
    SharedDatagridComponent = BaseComponent.extend({
        options: {
            messages: {}
        },

        listen: {
            'datagrid:mass:frontend:execute:shared-datagrid mediator': 'onFrontMassAction',
            'datagrid:frontend:execute:shared-datagrid mediator': 'onFrontAction',
            'datagrid:shared-datagrid:add:data mediator': 'onSharedWithDatagridAdd',
            'datagrid:shared-datagrid:add:data-from-select2 mediator': 'onSelect2Add',
            'widget:shared-dialog:apply mediator': 'onShareDialogApply'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            _.defaults(this.options.messages, {
                sharedSuccess: __('oropro.security.action.shared_success'),
                sharedError: __('oropro.security.action.shared_error'),
                forbiddenError: __('oropro.security.action.forbidden_error')
            });
        },

        onFrontMassAction: function(action) {
            _.each(helper.extractModelsFromGridCollection(action.datagrid), function(model) {
                action.datagrid.collection.get(model).trigger('backgrid:select', model, false);
                action.datagrid.removeRow(model, {silent: true});
                _.each(action.datagrid.body.rows, function(row) {
                    if (row.model.id === model.id) {
                        row.$el.remove();
                    }
                });
            });
            if (action.datagrid.collection.length === 0) {
                action.datagrid.collection.reset([]);
            }
        },

        onFrontAction: function(action) {
            action.datagrid.collection.get(action.model).trigger('backgrid:select', action.model, false);
            action.datagrid.removeRow(action.model, {silent: true});
            _.each(action.datagrid.body.rows, function(row) {
                if (row.model.id === action.model.id) {
                    row.$el.remove();
                }
            });
            if (action.datagrid.collection.length === 0) {
                action.datagrid.collection.reset([]);
            }
        },

        onSharedWithDatagridAdd: function(data) {
            widgetManager.getWidgetInstanceByAlias('shared-dialog', function(widget) {
                var grid = widget.pageComponent('shared-datagrid').grid;
                _.each(data.models, function(model) {
                    var id = JSON.stringify({
                        entityId: model.id,
                        entityClass: data.entityClass
                    });
                    if (!grid.collection.findWhere({id: id})) {
                        var newModel = {
                            id: id,
                            entity: model.get('entity')
                        };
                        grid.collection.add(newModel).trigger('backgrid:select', newModel, true);
                        grid.render().trigger('layout:update');
                    }
                });
            });
        },

        onSelect2Add: function(data) {
            widgetManager.getWidgetInstanceByAlias('shared-dialog', function(widget) {
                var grid = widget.pageComponent('shared-datagrid').grid;
                if (!grid.collection.findWhere({id: data.id})) {
                    var model = {
                        id: data.id,
                        entity: data.entity
                    };
                    grid.collection.add(model).trigger('backgrid:select', model, true);
                    grid.render().trigger('layout:update');
                }
            });
        },

        onShareDialogApply: function() {
            var self = this;
            widgetManager.getWidgetInstanceByAlias('shared-dialog', function(widget) {
                var grid = widget.pageComponent('shared-datagrid').grid;

                var entitiesParam = [];
                _.each(grid.collection.models, function(model) {
                    entitiesParam.push(model.id);
                });
                var finallyFunc = function(e) {
                    widgetManager.getWidgetInstanceByAlias('shared-dialog', function(widget) {
                        if (e.status === 200) {
                            mediator.execute('showFlashMessage', 'success', self._getMessage('sharedSuccess'));
                        } else if (e.status === 403) {
                            mediator.execute('showErrorMessage', self._getMessage('forbiddenError'), e);
                        } else {
                            mediator.execute('showErrorMessage', self._getMessage('sharedError'), e);
                        }

                        widget.remove();
                        mediator.execute('refreshPage');
                    });
                };
                grid.collection.sync('POST', grid.collection, {
                    method: 'POST',
                    url: routing.generate(
                        'oropro_share_update',
                        {
                            'entityId': self.options.entityId,
                            '_widgetContainer': 'dialog',
                            'entityClass': self.options.entityClass
                        }
                    ),
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    data: 'oropro_share_form%5BentityClass%5D=' + self.options.entityClass +
                        '&oropro_share_form%5BentityId%5D=' + self.options.entityId +
                        '&oropro_share_form%5Bentities%5D=' + encodeURIComponent(entitiesParam.join(';')),
                    wait: true,
                    error: finallyFunc,
                    success: finallyFunc
                });
            });
        },

        _getMessage: function(labelKey) {
            return this.options.messages[labelKey];
        }
    });

    return SharedDatagridComponent;
});
