define(function(require) {
    'use strict';

    var ActivityListView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var DialogWidget = require('oro/dialog-widget');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');

    ActivityListView = BaseCollectionView.extend({
        options: {
            configuration: {},
            template: null,
            itemTemplate: null,
            itemViewIdPrefix: 'activity-',
            listSelector: '.items.list-box',
            fallbackSelector: '.no-data',
            loadingSelector: '.loading-mask',
            collection: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            },
            messages: {},
            ignoreHead: false,
            doNotFetch: false
        },

        listen: {
            'toView collection': '_viewItem',
            'toViewGroup collection': '_viewGroup',
            'toEdit collection': '_editItem',
            'toDelete collection': '_deleteItem'
        },

        EDIT_DIALOG_CONFIGURATION_DEFAULTS: {
            'regionEnabled': false,
            'incrementalPosition': false,
            'alias': 'activity_list:item:update',
            'dialogOptions': {
                'modal': true,
                'resizable': true,
                'width': 675,
                'autoResize': true
            }
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            _.defaults(this.options.messages, {
                editDialogTitle: __('oro.activitylist.edit_title'),
                itemSaved: __('oro.activitylist.item_saved'),
                itemRemoved: __('oro.activitylist.item_removed'),

                deleteConfirmation: __('oro.activitylist.delete_confirmation'),
                deleteItemError: __('oro.activitylist.delete_error'),

                loadItemsError: __('oro.activitylist.load_error'),
                forbiddenError: __('oro.activitylist.forbidden_error'),
                forbiddenActivityDataError: __('oro.activitylist.forbidden_activity_data_view_error')
            });

            this.template = _.template($(this.options.template).html());
            this.isFiltersEmpty = true;
            this.gridToolbar = $('.activity-list-widget .activity-list .grid-toolbar');

            /**
             * on adding activity item listen to "widget:doRefresh:activity-list-widget"
             */
            mediator.on('widget:doRefresh:activity-list-widget', this._reloadOnAdd, this);

            /**
             * on editing activity item listen to "widget_success:activity_list:item:update"
             */
            mediator.on('widget_success:activity_list:item:update', this._reload, this);

            ActivityListView.__super__.initialize.call(this, options);

            if (!this.doNotFetch) {
                this._initPager();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.itemEditDialog;

            mediator.off('widget:doRefresh:activity-list-widget', this._reloadOnAdd, this);
            mediator.off('widget_success:activity_list:item:update', this._reload, this);

            ActivityListView.__super__.dispose.call(this);
        },

        initItemView: function(model) {
            var className = model.getRelatedActivityClass();
            var configuration = this.options.configuration[className];
            if (this.itemView) {
                return new this.itemView({
                    autoRender: false,
                    model: model,
                    configuration: configuration,
                    ignoreHead: this.options.ignoreHead
                });
            } else {
                ActivityListView.__super__.render.apply(this, arguments);
            }
        },

        refresh: function() {
            this.collection.setPage(1);
            this.collection.resetPageFilter();

            this._reload();

            mediator.trigger('widget_success:activity_list:refresh');
        },

        _initPager: function() {
            if (this.collection.getCount() && this.collection.getPage() == 1) {
                this._toggleNext(true);
            }

            if (this.collection.getPage() == 1) {
                this._togglePrevious();
            } else {
                this._togglePrevious(true);
            }

            if (this.collection.getCount() < this.collection.getPageSize()) {
                this._toggleNext();
            }

            if (this.collection.getCount() === 0 && this.isFiltersEmpty && this.collection.getPage() == 1) {
                this.gridToolbar.hide();
            } else {
                this.gridToolbar.show();
            }

            this.collection.setPageFilterAction();
        },

        /**
         * Fetches loading container element
         *
         *  - returns loading container passed over options,
         *    or the view element as default loading container
         *
         * @returns {HTMLElement|undefined}
         * @protected
         * @override
         */
        _getLoadingContainer: function() {
            var loadingContainer = this.options.loadingContainer;
            if (loadingContainer instanceof $) {
                // fetches loading container from options
                loadingContainer = loadingContainer.get(0);
            }
            if (!loadingContainer) {
                // uses the element as default loading container
                loadingContainer = this.$el.get(0);
            }
            return loadingContainer;
        },

        goto_previous: function() {
            var currentPage = this.collection.getPage();
            if (currentPage == 1) {
                return;
            }

            if (currentPage == 2) {
                this.collection.setPage(1);
                this.collection.resetPageFilter();

                this._reload();
            } else {
                var nextPage = currentPage - 1;
                this.collection.setPage(nextPage);

                var listFirstModel = this.collection.models[0];
                var listFirstModelId = listFirstModel.attributes.id;

                this.collection.setPageFilterDate(listFirstModel.attributes.updatedAt);
                this.collection.setPageFilterIds([listFirstModelId]);
                this.collection.setPageFilterAction('prev');

                this._reload();
            }

            this._toggleNext(true);
        },

        goto_next: function() {
            if (this.collection.getCount() < this.collection.getPageSize()) {
                return;
            }
            var currentPage = this.collection.getPage();

            this.collection.setPage(currentPage + 1);
            this.collection.setPageTotal(this.collection.getPageTotal() + 1);

            var listLastModel = this.collection.models[this.collection.getCount() - 1];
            var listLastModelId = listLastModel.attributes.id;

            this.collection.setPageFilterDate(listLastModel.attributes.updatedAt);
            this.collection.setPageFilterIds([listLastModelId]);
            this.collection.setPageFilterAction('next');

            this._reload();
        },

        _togglePrevious: function(enable) {
            if (_.isUndefined(enable)) {
                $('.activity-list-widget .pagination-previous').addClass('disabled');
            } else {
                $('.activity-list-widget .pagination-previous').removeClass('disabled');
            }
        },

        _toggleNext: function(enable) {
            if (_.isUndefined(enable)) {
                $('.activity-list-widget .pagination-next').addClass('disabled');
            } else {
                $('.activity-list-widget .pagination-next').removeClass('disabled');
            }
        },

        _reloadOnAdd: function() {
            if (this.collection.getPage() == 1) {
                this._reload();
            }
        },

        _reload: function() {
            var itemViews;
            // please note that _hideLoading will be called in renderAllItems() function
            this._showLoading();
            if (this.options.doNotFetch) {
                this._hideLoading();
                return;
            }
            try {
                // store views state
                this.oldViewStates = {};
                itemViews = this.getItemViews();
                this.oldViewStates = _.map(itemViews, function(view) {
                    return {
                        attrs: view.model.toJSON(),
                        collapsed: view.isCollapsed(),
                        height: view.$el.height()
                    };
                });

                this.collection.fetch({
                    reset: true,
                    success: _.bind(this._initPager, this),
                    error: _.bind(function(collection, response) {
                        this._showLoadItemsError(response.responseJSON || {});
                    }, this)
                });
            } catch (err) {
                this._showLoadItemsError(err);
            }
        },

        renderAllItems: function() {
            var result;
            var i;
            var view;
            var model;
            var oldViewState;
            var contentLoadedPromises;
            var deferredContentLoading;

            result = ActivityListView.__super__.renderAllItems.apply(this, arguments);

            contentLoadedPromises = [];

            if (this.oldViewStates) {
                // restore state
                for (i = 0; i < this.oldViewStates.length; i++) {
                    oldViewState = this.oldViewStates[i];
                    model = this.collection.findSameActivity(oldViewState.attrs);
                    if (model) {
                        view = this.getItemView(model);
                        if (view && !oldViewState.collapsed && view.isCollapsed()) {
                            view.toggle();
                            view.getAccorditionBody().addClass('in');
                            view.getAccorditionToggle().removeClass('collapsed');
                            if (view.model.get('isContentLoading')) {
                                // if model is loading - need to wait until content will be loaded before _hideLoading()
                                // also preserve height during loading
                                view.$el.height(oldViewState.height);
                                deferredContentLoading = $.Deferred();
                                contentLoadedPromises.push(deferredContentLoading);
                                view.model.once(
                                    'change:isContentLoading',
                                    _.bind(function(view, deferredContentLoading) {
                                        // reset height
                                        view.$el.height('');
                                        deferredContentLoading.resolve();
                                    }, this, view, deferredContentLoading)
                                );
                            }
                        }
                    }
                }
                delete this.oldViewStates;
            }

            $.when.apply($, contentLoadedPromises).done(_.bind(function() {
                this._hideLoading();
            }, this));

            return result;
        },

        _viewItem: function(model) {
            this._loadModelContentHTML(model, 'itemView');
        },

        _viewGroup: function(model) {
            this._loadModelContentHTML(model, 'groupView');
        },

        _loadModelContentHTML: function(model, actionKey) {
            var url = this._getUrl(actionKey, model);
            if (model.get('is_loaded') === true) {
                return;
            }
            model.loadContentHTML(url)
                .fail(_.bind(function(response) {
                    if (response.status === 403) {
                        this._showForbiddenActivityDataError(response.responseJSON || {});
                    } else {
                        this._showLoadItemsError(response.responseJSON || {});
                    }
                }, this));
        },

        _editItem: function(model) {
            if (!this.itemEditDialog) {
                var unescapeHTML = function unescapeHtml(unsafe) {
                    return unsafe
                        .replace(/&nbsp;/g, ' ')
                        .replace(/&amp;/g, '&')
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>')
                        .replace(/&quot;/g, '\"')
                        .replace(/&#039;/g, '\'');
                };

                var dialogConfiguration = $.extend(true, {}, this.EDIT_DIALOG_CONFIGURATION_DEFAULTS, {
                    'url': this._getUrl('itemEdit', model),
                    'title': unescapeHTML(model.get('subject')),
                    'dialogOptions': {
                        'close': _.bind(function() {
                            delete this.itemEditDialog;
                        }, this)
                    }
                });
                this.itemEditDialog = new DialogWidget(dialogConfiguration);

                this.itemEditDialog.render();
            }
        },

        _deleteItem: function(model) {
            var confirm = new DeleteConfirmation({
                content: this._getMessage('deleteConfirmation')
            });
            confirm.on('ok', _.bind(function() {
                this._onItemDelete(model);
            }, this));
            confirm.open();
        },

        _onItemDelete: function(model) {
            this._showLoading();
            try {
                model.destroy({
                    wait: true,
                    url: this._getUrl('itemDelete', model),
                    success: _.bind(function() {
                        mediator.execute('showFlashMessage', 'success', this._getMessage('itemRemoved'));
                        mediator.trigger('widget_success:activity_list:item:delete');

                        this._reload();
                    }, this),
                    error: _.bind(function(model, response) {
                        if (!_.isUndefined(response.status) && response.status === 403) {
                            this._showForbiddenError(response.responseJSON || {});
                        } else {
                            this._showDeleteItemError(response.responseJSON || {});
                        }
                        this._hideLoading();
                    }, this)
                });
            } catch (err) {
                this._showDeleteItemError(err);
                this._hideLoading();
            }
        },

        /**
         * Fetches url for certain action
         *
         * @param {string} actionKey
         * @param {Backbone.Model=}model
         * @returns {string}
         * @protected
         */
        _getUrl: function(actionKey, model) {
            var className = model.getRelatedActivityClass();
            var route = this.options.configuration[className].routes[actionKey];
            return routing.generate(route, {'id': model.get('relatedActivityId')});
        },

        _getMessage: function(labelKey) {
            return this.options.messages[labelKey];
        },

        _showLoading: function() {
            this.subview('loading').show();
        },

        _hideLoading: function() {
            this.subview('loading').hide();
        },

        _showLoadItemsError: function(err) {
            this._showError(this.options.messages.loadItemsError, err);
        },

        _showDeleteItemError: function(err) {
            this._showError(this.options.messages.deleteItemError, err);
        },

        _showForbiddenActivityDataError: function(err) {
            this._showError(this.options.messages.forbiddenActivityDataError, err);
        },

        _showForbiddenError: function(err) {
            this._showError(this.options.messages.forbiddenError, err);
        },

        _showError: function(message, err) {
            this._hideLoading();
            mediator.execute('showErrorMessage', message, err);
        }
    });

    return ActivityListView;
});
