define(function(require) {
    'use strict';

    var DialogWidget;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var error = require('oroui/js/error');
    var messenger = require('oroui/js/messenger');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var AbstractWidget = require('oroui/js/widget/abstract-widget');
    var StateModel = require('orowindows/js/dialog/state/model');
    require('jquery.dialog.extended');

    /**
     * @export  oro/dialog-widget
     * @class   oro.DialogWidget
     * @extends oroui.widget.AbstractWidget
     */
    DialogWidget = AbstractWidget.extend({
        options: _.extend({}, AbstractWidget.prototype.options, {
            type: 'dialog',
            dialogOptions: null,
            stateEnabled: true,
            incrementalPosition: true
        }),

        // Windows manager global variables
        windowsPerRow: 10,
        windowOffsetX: 15,
        windowOffsetY: 15,
        windowX: 0,
        windowY: 0,
        defaultPos: 'center center',
        openedWindows: 0,
        contentTop: null,
        /**
         * Flag if the widget is embedded to the page
         * (dialog has own life cycle)
         *
         * @type {boolean}
         */
        _isEmbedded: false,

        listen: {
            'adoptedFormResetClick': 'remove',
            'widgetRender': '_initAdjustHeight',
            'contentLoad': 'onContentUpdated',
            'page:request mediator': 'onPageChange'
        },

        /**
         * Initialize dialog
         */
        initialize: function(options) {
            var dialogOptions;
            options = options || {};
            this.options = _.defaults(options, this.options);

            dialogOptions = options.dialogOptions = options.dialogOptions || {};
            _.defaults(dialogOptions, {
                title: options.title,
                limitTo: '#container',
                minWidth: 375,
                minHeight: 150
            });
            if (tools.isMobile()) {
                options.incrementalPosition = false;
                options.dialogOptions.limitTo = 'body';
            }

            // it's possible to track state only for not modal dialogs
            options.stateEnabled = options.stateEnabled && !dialogOptions.modal;
            if (options.stateEnabled) {
                this._initModel();
            }

            dialogOptions.beforeClose = _.bind(this.closeHandler, this, dialogOptions.close);
            dialogOptions.close = undefined;

            this.initializeWidget(options);
        },

        setTitle: function(title) {
            this.widget.dialog('option', 'title', title);
        },

        _initModel: function() {
            if (this.model) {
                this.restoreMode = true;
                var attributes = this.model.get('data');
                $.extend(true, this.options, attributes);
                if (this.options.el) {
                    this.setElement(this.options.el);
                } else if (this.model.get('id')) {
                    var restoredEl = $('#widget-restored-state-' + this.model.get('id'));
                    if (restoredEl.length) {
                        this.setElement(restoredEl);
                    }
                }
            } else {
                this.model = new StateModel();
            }
        },

        /**
         * Handles dialog close action
         *  - executes external close handler
         *  - disposes dialog widget
         *
         * @param {Function|undefined} onClose External onClose handler
         */
        closeHandler: function(onClose) {
            if (_.isFunction(onClose)) {
                onClose();
            }
            this.dispose();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.model) {
                this.model.destroy({
                    error: _.bind(function(model, xhr) {
                        // Suppress error if it's 404 response and not debug mode
                        if (xhr.status !== 404 || tools.debug) {
                            error.handle({}, xhr, {enforce: true});
                        }
                    }, this)
                });
            }
            this._hideLoading();

            // need to remove components in widget before DOM will be deleted
            this.disposePageComponents();
            if (this.widget) {
                this.widget.remove();
                delete this.widget;
            }

            DialogWidget.__super__.dispose.call(this);
        },

        /**
         * Returns flag if the widget is embedded to the parent content
         *
         * @returns {boolean}
         */
        isEmbedded: function() {
            // modal dialogs has same life cycle as embedded widgets
            return this._isEmbedded || this.options.dialogOptions.modal;
        },

        /**
         * Handles content load event and sets focus on first form input
         */
        onContentUpdated: function() {
            this.$('form:first').focusFirstInput();
        },

        /**
         * Handle content loading failure.
         * @private
         */
        _onContentLoadFail: function(jqxhr) {
            this.options.stateEnabled = false;
            if (jqxhr.status === 403) {
                messenger.notificationFlashMessage('error', __('oro.ui.forbidden_error'));
                this.remove();
            } else {
                DialogWidget.__super__._onContentLoadFail.apply(this, arguments);
            }
        },

        handleStateChange: function(e, data) {
            if (!this.options.stateEnabled) {
                return;
            }
            if (this.restoreMode) {
                this.restoreMode = false;
                return;
            }
            var saveData = _.omit(this.options, ['dialogOptions', 'el', 'model']);
            if (!saveData.url) {
                saveData.el = $('<div/>').append(this.$el.clone()).html();
            }
            saveData.dialogOptions = {};
            _.each(this.options.dialogOptions, function(val, key) {
                if (!_.isFunction(val) && key !== 'position') {
                    saveData.dialogOptions[key] = val;
                }
            }, this);

            saveData.dialogOptions.title = $(e.target).dialog('option', 'title');
            saveData.dialogOptions.state = data.state;
            saveData.dialogOptions.snapshot = data.snapshot;
            saveData.wid = this.getWid();
            if (this.model) {
                this.model.save({data: saveData});
            }
        },

        /**
         * Handles page change
         *  - closes dialogs with not tracked state (eg. modal dialogs)
         */
        onPageChange: function() {
            if (!this.options.stateEnabled) {
                this.remove();
            }
        },

        /**
         * Removes dialog widget
         */
        remove: function() {
            if (this.widget) {
                // There's widget, close it before remove.
                // Close handler will invoke dispose method,
                // where remove method will be called again
                this.widget.dialog('close');
            } else {
                DialogWidget.__super__.remove.call(this);
            }
        },

        getWidget: function() {
            return this.widget;
        },

        /**
         * @inheritDoc
         */
        getLayoutElement: function() {
            // covers not only widget body, but whole .ui-dialog, including .ui-dialog-buttonpane
            return this.widget.parent();
        },

        getActionsElement: function() {
            if (!this.actionsEl) {
                this.actionsEl = $('<div class="pull-right"/>').appendTo(
                    $('<div class="form-actions widget-actions"/>').appendTo(
                        this.widget.dialog('actionsContainer')
                    )
                );
            }
            return this.actionsEl;
        },

        _clearActionsContainer: function() {
            this.widget.dialog('actionsContainer').empty();
        },

        _renderActions: function() {
            DialogWidget.__super__._renderActions.apply(this);
            if (this.hasActions()) {
                this.widget.dialog('showActionsContainer');
            }
        },

        /**
         * Show dialog
         */
        show: function() {
            var dialogOptions;
            if (!this.widget) {
                dialogOptions = _.extend({}, this.options.dialogOptions);
                if (typeof dialogOptions.position === 'undefined') {
                    dialogOptions.position = this._getWindowPlacement();
                }
                dialogOptions.stateChange = _.bind(this.handleStateChange, this);
                if (dialogOptions.state !== 'minimized') {
                    dialogOptions.dialogClass = 'invisible ' + (dialogOptions.dialogClass || '');
                }
                this.widget = $('<div/>');
                this._bindDialogEvents();
                this.widget.html(this.$el).dialog(dialogOptions);
                this.getLayoutElement().attr('data-layout', 'separate');
            } else {
                this.widget.html(this.$el);
            }
            this.loadingElement = this.$el.closest('.ui-dialog');
            DialogWidget.__super__.show.apply(this);

            this._fixDialogMinHeight(true);
            this.widget.on('dialogmaximize dialogrestore', _.bind(function() {
                this._fixDialogMinHeight(true);
                this.widget.trigger('resize');
            }, this));
            this.widget.on('dialogminimize', _.bind(function() {
                this._fixDialogMinHeight(false);
                this.widget.trigger('resize');
            }, this));
        },

        _afterLayoutInit: function() {
            this.widget.closest('.invisible').removeClass('invisible');
            if (this.deferredRender) {
                this._resolveDeferredRender();
            }
        },

        _initAdjustHeight: function(content) {
            this.widget.off('.adjust-height-events');
            var scrollableContent = content.find('.scrollable-container');
            if (scrollableContent.length) {
                scrollableContent.css('overflow', 'auto');
                var events = [
                    'dialogresize.adjust-height-events',
                    'dialogmaximize.adjust-height-events',
                    'dialogrestore.adjust-height-events'
                ];
                this.widget.on(events.join(''), _.bind(this._fixScrollableHeight, this));
                this._fixScrollableHeight();
            }
        },

        _fixDialogMinHeight: function(isEnabled) {
            if (isEnabled) {
                var minHeight = this.options.dialogOptions.minHeight + this.widget.dialog('actionsContainer').height();
                this.widget.dialog('widget').css('min-height', minHeight);
            } else {
                this.widget.dialog('widget').css('min-height', 0);
            }
        },

        _fixScrollableHeight: function() {
            var widget = this.widget;
            if (!tools.isMobile()) {
                // on mobile devices without setting these properties modal dialogs cannot be scrolled
                widget.find('.scrollable-container').each(_.bind(function(i, el) {
                    var $el = $(el);
                    var height = widget.height() - $el.position().top;
                    if (height) {
                        $el.outerHeight(height);
                    }
                }, this));
            }
            layout.updateResponsiveLayout();
        },

        /**
         * Get next window position based
         *
         * @returns {{my: string, at: string, of: (*|jQuery|HTMLElement), within: (*|jQuery|HTMLElement)}}
         * @private
         */
        _getWindowPlacement: function() {
            var prototype = DialogWidget.prototype;
            if (!this.options.incrementalPosition) {
                return {
                    my: 'center center',
                    at: prototype.defaultPos
                };
            }
            var offset = 'center+' + prototype.windowX + ' center+' + prototype.windowY;

            prototype.openedWindows++;
            if (prototype.openedWindows % prototype.windowsPerRow === 0) {
                var rowNum = prototype.openedWindows / prototype.windowsPerRow;
                prototype.windowX = rowNum * prototype.windowsPerRow * prototype.windowOffsetX;
                prototype.windowY = 0;

            } else {
                prototype.windowX += prototype.windowOffsetX;
                prototype.windowY += prototype.windowOffsetY;
            }

            return {
                my: offset,
                at: prototype.defaultPos
            };
        },

        /**
         * Returns state of the dialog
         *
         * @returns {string}
         */
        getState: function() {
            return this.widget.dialog('state');
        },

        /**
         * Binds dialog window state events,
         * Transmits open/close/statechange events over system message bus
         *
         * @protected
         */
        _bindDialogEvents: function() {
            var self = this;
            this.widget.on('dialogbeforeclose', function() {
                mediator.trigger('widget_dialog:close', self);
            });
            this.widget.on('dialogopen', function() {
                mediator.trigger('widget_dialog:open', self);
            });
            this.widget.on('dialogstatechange', function(event, data) {
                if (data.state !== data.oldState) {
                    mediator.trigger('widget_dialog:stateChange', self);
                }
            });
            this.widget.on({
                'dialogresizestart': _.bind(this.onResizeStart, this),
                'dialogresize dialogmaximize dialogrestore': _.bind(this.onResize, this),
                'dialogresizestop': _.bind(this.onResizeStop, this)
            });
        },

        onResizeStart: function(event) {
            this.$el.css({overflow: 'hidden'});
            this.forEachComponent(function(component) {
                component.trigger('parentResizeStart', event, this);
            });
        },

        onResize: function(event) {
            this.forEachComponent(function(component) {
                component.trigger('parentResize', event, this);
            });
        },

        onResizeStop: function(event) {
            this.$el.css({overflow: ''});
            this.forEachComponent(function(component) {
                component.trigger('parentResizeStop', event, this);
            });
        }
    });

    return DialogWidget;
});
