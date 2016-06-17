define(function() {
    'use strict';

    return {
        extractModelsFromGridCollection: function(datagrid) {
            var state = datagrid.getSelectionState();
            return datagrid.collection.filter(function(model) {
                return (state.selectedIds.indexOf(model.get('id')) !== -1) === state.inset;
            });
        }
    };
});
