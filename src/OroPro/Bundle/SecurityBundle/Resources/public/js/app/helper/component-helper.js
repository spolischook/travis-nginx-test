define(function(require) {
    'use strict';

    var _ = require('underscore');

    var ComponentHelper = {
        extractModelsFromGridCollection: function(datagrid) {
            var selectionState = datagrid.getSelectionState();
            var inSet = selectionState.inset;
            var models = [];
            if (inSet) {
                _.each(datagrid.collection.models, function(model) {
                    if (Object.keys(selectionState.selectedIds).length > 0) {
                        _.each(selectionState.selectedIds, function(selectedId) {
                            if (selectedId === model.id) {
                                models.push(model);
                            }
                        });
                    }
                });
            } else {
                _.each(datagrid.collection.models, function(model) {
                    if (Object.keys(selectionState.selectedIds).length > 0) {
                        _.each(selectionState.selectedIds, function(selectedId) {
                            var selectedModelMatched = false;
                            if (selectedId === model.id) {
                                selectedModelMatched = true;
                            }
                            if (!selectedModelMatched) {
                                models.push(model);
                            }
                        });
                    } else {
                        models.push(model);
                    }
                });
            }

            return models;
        }
    };

    return ComponentHelper;
});
