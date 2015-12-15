define(function(require) {
    'use strict';

    require('jasmine-jquery');
    define('mock/condition-builder/matrix-condition', function() {});
    define('mock/condition-builder/condition-item', function() {});

    var $ = require('oroquerydesigner/js/condition-builder');
    var html = require('text!./Fixture/condition-builder/markup.html');
    var initialValue = JSON.parse(require('text!./Fixture/condition-builder/initial-value.json'));
    var runtimeValue = JSON.parse(require('text!./Fixture/condition-builder/runtime-value.json'));
    var conditionBuilderSlector = '#condition-builder';
    var criteriaListSelector = '#criteria-list';
    var sourceValueSelector = '#condition-value';

    describe('oroquerydesigner/js/condition-builder', function() {
        var $el = null;
        var toDelete;

        // emulates drag and drop action
        function changeHierarchy($item1, position, $item2) {
            var sender = $item1.parent();
            if (position === 'before') {
                $item1.insertBefore($item2);
            } else {
                $item1.insertAfter($item2);
            }
            // emulates remove handler of sortable, which triggers 'changed'
            sender.trigger('changed');
            // emulates update handler of sortable, call handler directly
            $el.data('oroquerydesigner-conditionBuilder')
                ._onHierarchyChange(null, {sender: sender, item: $item1});
        }

        function initialized() {
            return $el.data('oroquerydesigner-conditionBuilder').$rootCondition.data('initialized');
        }

        beforeEach(function(done) {
            window.setFixtures(html);
            $el = $(conditionBuilderSlector);
            toDelete = [];

            if ($.fn.conditionItem) {
                spyOn($.fn, 'conditionItem');
            } else {
                toDelete.push('conditionItem');
                $.fn.conditionItem = jasmine.createSpy('conditionItem');
            }

            if ($.fn.matrixCondition) {
                spyOn($.fn, 'matrixCondition');
            } else {
                toDelete.push('matrixCondition');
                $.fn.matrixCondition = jasmine.createSpy('matrixCondition');
            }

            spyOn($.fn, 'dropdownSelect');
            $(sourceValueSelector).val(JSON.stringify(initialValue));
            $el.conditionBuilder({
                criteriaListSelector: criteriaListSelector,
                sourceValueSelector: sourceValueSelector
            });
            var checker = setInterval(function() {
                if (initialized()) {
                    clearInterval(checker);
                    done();
                }
            }, 0);
        });

        afterEach(function() {
            var i;
            for (i = 0; i < toDelete.length; i += 1) {
                delete $.fn[toDelete[i]];
            }
            $el = null;
        });

        it('calls getValue public', function() {
            var value = $el.conditionBuilder('getValue');
            expect(value).toEqual(initialValue);
        });

        describe('container structure', function() {
            describe('groups', function() {
                it('counts elements', function() {
                    var groups = $el.find('[data-criteria=conditions-group]');
                    expect(groups).toHaveLength(1);
                });

                it('checks values', function() {
                    var groups = $el.find('[data-criteria=conditions-group]');
                    var values = groups.map(function() {
                        return $(this).find('>.conditions-group[data-value]').data('value');
                    }).get();
                    expect(values).toEqual([{equal: 5}, 'OR', {criteria: 'matrix-condition', less: 8}]);
                });
            });

            describe('matrix-conditions', function() {
                it('counts elements', function() {
                    var matrixConditions = $el.find('[data-criteria=matrix-condition]');
                    expect(matrixConditions).toHaveLength(2);
                });

                it('checks if matrixCondition widget appended', function() {
                    expect($.fn.matrixCondition.calls.count()).toEqual(2);
                });

                it('checks options of matrixCondition widget', function() {
                    expect($.fn.matrixCondition.calls.mostRecent().args[0]).toEqual({name: 'The Matrix Condition'});
                });

                it('checks values', function() {
                    var matrixConditions = $el.find('[data-criteria=matrix-condition]');
                    var values = matrixConditions.map(function() {
                        return $(this).find('>.condition-item[data-value]').data('value');
                    }).get();
                    expect(values).toEqual([
                        {great: 10, criteria: 'matrix-condition'},
                        {less: 8, criteria: 'matrix-condition'}
                    ]);
                });
            });

            describe('condition-items', function() {
                it('counts elements', function() {
                    var conditionItems = $el.find('[data-criteria=condition-item]');
                    expect(conditionItems).toHaveLength(1);
                });

                it('checks if conditionItem widget appended', function() {
                    expect($.fn.conditionItem.calls.count()).toEqual(1);
                });

                it('checks options of conditionItem widget', function() {
                    expect($.fn.conditionItem.calls.mostRecent().args[0]).toEqual({name: 'The Condition Item'});
                });

                it('checks values', function() {
                    var conditionItems = $el.find('[data-criteria=condition-item]');
                    var values = conditionItems.map(function() {
                        return $(this).find('>.condition-item[data-value]').data('value');
                    }).get();
                    expect(values).toEqual([{equal: 5}]);
                });
            });

            describe('operators', function() {
                it('counts elements', function() {
                    var operators = $el.find('.operator[data-value]');
                    expect(operators).toHaveLength(2);
                });

                it('checks if dropdownSelect widget appended', function() {
                    expect($.fn.dropdownSelect.calls.count()).toEqual(2);
                });

                it('checks values', function() {
                    var operators = $el.find('.operator[data-value]');
                    var values = operators.map(function() {
                        return $(this).data('value');
                    }).get();
                    expect(values).toEqual(['AND', 'OR']);
                });
            });
        });

        describe('restructure process', function() {
            it('moves group at the beginning', function() {
                var group = $el.find('[data-criteria=conditions-group]');
                var matrixCondition1 = $el.find('[data-criteria=matrix-condition]:first');
                changeHierarchy(group, 'before', matrixCondition1);
                expect($el.conditionBuilder('getValue')).toEqual([
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ],
                    'AND',
                    {criteria: 'matrix-condition', great: 10}
                ]);
            });

            it('moves condition-item inside group', function() {
                var matrixCondition2 = $el.find('[data-criteria=matrix-condition]:last');
                var conditionItems = $el.find('[data-criteria=condition-item]');
                changeHierarchy(matrixCondition2, 'before', conditionItems);
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {criteria: 'matrix-condition', less: 8},
                        'AND',
                        {equal: 5}
                    ]
                ]);
            });

            it('puts condition-item outside group', function() {
                var matrixCondition1 = $el.find('[data-criteria=matrix-condition]:first');
                var matrixCondition2 = $el.find('[data-criteria=matrix-condition]:last');
                changeHierarchy(matrixCondition2, 'after', matrixCondition1);
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'OR',
                    {criteria: 'matrix-condition', less: 8},
                    'AND',
                    [
                        {equal: 5}
                    ]
                ]);
            });

            it('puts condition-item into group', function() {
                var matrixCondition1 = $el.find('[data-criteria=matrix-condition]:first');
                var matrixCondition2 = $el.find('[data-criteria=matrix-condition]:last');
                changeHierarchy(matrixCondition1, 'after', matrixCondition2);
                expect($el.conditionBuilder('getValue')).toEqual([
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8},
                        'AND',
                        {criteria: 'matrix-condition', great: 10}
                    ]
                ]);
            });
        });

        describe('add a new condition', function() {
            it('adds "matrix condition" into group', function() {
                var matrixCondition2 = $el.find('[data-criteria=matrix-condition]:last');
                var matrixConditionCriteria = $(criteriaListSelector + '>[data-criteria=matrix-condition]');
                var criteria = matrixConditionCriteria.clone().insertAfter(matrixConditionCriteria);
                changeHierarchy(criteria, 'after', matrixCondition2);
                expect($.fn.matrixCondition.calls.count()).toEqual(3);
                expect($.fn.matrixCondition.calls.mostRecent().args[0]).toEqual({name: 'The Matrix Condition'});
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8},
                        'AND',
                        {}
                    ]
                ]);
            });

            it('adds "condition item" before group', function() {
                var group = $el.find('[data-criteria=conditions-group]');
                var conditionItemCriteria = $(criteriaListSelector + '>[data-criteria=condition-item]');
                var criteria = conditionItemCriteria.clone().insertAfter(conditionItemCriteria);
                changeHierarchy(criteria, 'before', group);
                expect($.fn.conditionItem.calls.count()).toEqual(2);
                expect($.fn.conditionItem.calls.mostRecent().args[0]).toEqual({name: 'The Condition Item'});
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    {},
                    'AND',
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });

            it('adds a new group inside the group', function() {
                var conditionItem = $el.find('[data-criteria=condition-item]');
                var conditionsGroupCriteria = $(criteriaListSelector + '>[data-criteria=conditions-group]');
                var criteria = conditionsGroupCriteria.clone().insertAfter(conditionsGroupCriteria);
                changeHierarchy(criteria, 'before', conditionItem);
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        [  ],
                        'AND',
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });
        });

        describe('condition-item\'s value change', function() {
            it('changes value of a condition-item inside group', function() {
                var matrixCondition = $el.find('[data-criteria=matrix-condition]:first');
                var content = matrixCondition.find('>[data-value]:not(.operator)');
                content.data('value', {great: 18}).trigger('changed');
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 18},
                    'AND',
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });

            it('changes value of a condition-item in a root', function() {
                var conditionItem = $el.find('[data-criteria=condition-item]');
                var content = conditionItem.find('>[data-value]:not(.operator)');
                content.data('value', {less: -8}).trigger('changed');
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {less: -8},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });
        });

        describe('operator\'s value change', function() {
            it('changes value of the operator inside group', function() {
                $el.find('[data-criteria=matrix-condition]>.operator')
                    .trigger($.Event('change', {value: 'AND'}));
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {equal: 5},
                        'AND',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });

            it('changes value of the operator before group', function() {
                $el.find('[data-criteria=conditions-group]>.operator')
                    .trigger($.Event('change', {value: 'OR'}));
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'OR',
                    [
                        {equal: 5},
                        'OR',
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });
        });

        describe('validation\'s checkboxes', function() {
            it('checks validation input for condition-item', function() {
                var conditionItem = $el.find('[data-criteria=condition-item]');
                var content = conditionItem.find('>[data-value]:not(.operator)');
                var input = conditionItem.find('>input[name^=condition_item_]');
                expect(input).toBeChecked();
                content.data('value', {}).trigger('changed');
                expect(input).not.toBeChecked();
            });

            it('checks validation input for group', function() {
                var conditionsGroup = $el.find('[data-criteria=conditions-group]');
                var content = conditionsGroup.find('>[data-value]:not(.operator)');
                var input = conditionsGroup.find('>input[name^=condition_item_]');
                expect(input).toBeChecked();
                content.empty().trigger('changed');
                expect(input).not.toBeChecked();
            });
        });

        describe('close condition', function() {
            it('closes condition-item', function() {
                var conditionItem = $el.find('[data-criteria=condition-item]');
                conditionItem.find('>a.close').trigger('click');
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10},
                    'AND',
                    [
                        {criteria: 'matrix-condition', less: 8}
                    ]
                ]);
            });

            it('closes group', function() {
                var conditionsGroup = $el.find('[data-criteria=conditions-group]');
                conditionsGroup.find('>a.close').trigger('click');
                expect($el.conditionBuilder('getValue')).toEqual([
                    {criteria: 'matrix-condition', great: 10}
                ]);
            });
        });

        describe('new value', function() {
            it('checks new structure', function() {
                $el.conditionBuilder('setValue', runtimeValue);
                expect($el.find('[data-criteria=conditions-group]')).toHaveLength(3);
                expect($el.find('[data-criteria=matrix-condition]')).toHaveLength(3);
                expect($el.find('[data-criteria=condition-item]')).toHaveLength(4);
                expect($el.find('.operator[data-value]')).toHaveLength(6);
                expect($el.conditionBuilder('getValue')).toEqual(runtimeValue);
            });
        });
    });
});
