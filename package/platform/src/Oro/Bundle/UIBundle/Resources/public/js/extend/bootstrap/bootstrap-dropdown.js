define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var scrollHelper = require('oroui/js/tools/scroll-helper');
    require('bootstrap');
    var toggleDropdown = '[data-toggle=dropdown]';

    function getParent($this) {
        var selector = $this.attr('data-target');
        var $parent;
        if (!selector) {
            selector = $this.attr('href');
            selector = selector && /#/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, ''); //strip for ie7
        }
        $parent = selector && $(selector);
        if (!$parent || !$parent.length) {
            $parent = $this.parent();
        }
        return $parent;
    }

    function beforeClearMenus() {
        $(toggleDropdown).each(function() {
            var $parent = getParent($(this));
            if ($parent.hasClass('open')) {
                $parent.trigger('hide.bs.dropdown');
            }
            $(this).dropdown('detach', false);
        });
    }

    /**
     * Override for Dropdown constructor
     *  - added destroy method, which removes event handlers from <html /> node
     *  - overloaded click handler on toggleDropdown element
     *    * executes custom clearMenu method
     *    * triggers 'shown.bs.dropdown' event on '.dropdown-menu' parent
     *
     * @param {HTMLElement} element
     * @constructor
     */
    function Dropdown(element) {
        var $el = $(element).on('click.dropdown.data-api', this.toggle);
        var globalHandlers = {
            'click.dropdown.data-api': function() {
                var $dropdown = $el.parent();
                if ($dropdown.is('.open')) {
                    $dropdown.trigger('hide.bs.dropdown').removeClass('open');
                }
                $el.dropdown('detach', false);
            }
        };
        $el.data('globalHandlers', globalHandlers);
        $('html').on(globalHandlers);
    }

    Dropdown.prototype = $.fn.dropdown.Constructor.prototype;

    $(document).off('click.dropdown.data-api', toggleDropdown, Dropdown.prototype.toggle);
    Dropdown.prototype.toggle = _.wrap(Dropdown.prototype.toggle, function(func, event) {
        beforeClearMenus();
        var result = func.apply(this, _.rest(arguments));

        var $parent = getParent($(this));
        if ($parent.hasClass('open')) {
            $parent.trigger('shown.bs.dropdown');
        }

        $(this).dropdown('detach');
        return result;
    });

    Dropdown.prototype.detach = function(isActive) {
        var $this = $(this);
        var container = $this.data('container');
        var $container;
        if (!container || !($container = $this.closest(container)).length) {
            return;
        }

        var $parent = getParent($this);
        isActive = isActive !== undefined ? isActive : $parent.hasClass('open');
        var $dropdownMenu;
        var $placeholder;

        if (isActive && ($dropdownMenu = $parent.find('.dropdown-menu:first')).length) {
            var css = _.extend(_.pick($dropdownMenu.offset(), ['top', 'left']), {
                display: 'block'
            });

            var options = $dropdownMenu.data('options');
            if (options && options.align === 'right') {
                css.right = $(window).width() - css.left - $dropdownMenu.outerWidth();
                css.left = 'auto';
            }

            var originalPosition = {
                parent: $parent.offset(),
                dropdownMenu: $dropdownMenu.offset()
            };
            $placeholder = $('<div class="dropdown-menu__placeholder"/>');
            $dropdownMenu.data('related-toggle', $this);
            $placeholder.data('related-menu', $dropdownMenu);

            /**
             * Add detach class for dropdown, remember parent and dropdown styles.
             * Then move dropdown to container and apply styles
             */
            $dropdownMenu.addClass('detach');
            $dropdownMenu.after($placeholder)
                .appendTo($container)
                .css(css);

            $this.data('related-data', {
                $dropdownMenu: $dropdownMenu,
                $placeholder: $placeholder,
                originalPosition: originalPosition
            }).dropdown('updatePosition');
        } else if (!isActive && ($placeholder = $parent.find('.dropdown-menu__placeholder')).length) {
            $dropdownMenu = $placeholder.data('related-menu')
                .removeAttr('style')
                .removeClass('detach');

            $this.removeData('related-data');
            $placeholder.before($dropdownMenu).remove();
        }
    };

    Dropdown.prototype.updatePosition = function() {
        /**
         * Sometimes, when dropdown opens in scrollable content, appears scrollbars.
         * Scrollbars decrease content height and width, change elements position.
         * When dropdown moved to container - scrollbars hiding and content returns to old dimensions and position.
         * Styles that was applied to dropdown are wrong, because dimensions and position are changed.
         * Following code fixes dropdown styles after that changes.
         */
        var obj = $(this).data('related-data');
        if (typeof obj !== 'object') {
            return false;
        }
        var $parent =  obj.$placeholder.parent();
        var $dropdownMenu = obj.$dropdownMenu;
        var dropdownMenuOriginalPosition = obj.originalPosition.dropdownMenu;
        var parentOriginalPosition = obj.originalPosition.parent;
        var parentPosition = $parent.offset();
        var css = {
            top: dropdownMenuOriginalPosition.top + parentPosition.top - parentOriginalPosition.top,
            left: dropdownMenuOriginalPosition.left + parentPosition.left - parentOriginalPosition.left
        };
        var options = $dropdownMenu.data('options');
        if (options && options.align === 'right') {
            css.right = $(window).width() - css.left - $dropdownMenu.outerWidth();
            css.left = 'auto';
        }
        $dropdownMenu.css(css);
    };

    $(document)
        .on('click.dropdown.data-api', toggleDropdown, Dropdown.prototype.toggle)
        .on('tohide.bs.dropdown', toggleDropdown + ', .dropdown.open, .dropup.open', function(e) {
            /**
             * Performs safe hide action for dropdown and triggers 'hide.bs.dropdown'
             * (the event 'tohide.bs.dropdown' have to be triggered on toggleDropdown or dropdown elements)
             */
            var $target = $(e.target);
            if ($target.is(toggleDropdown)) {
                $target = getParent($target);
            }
            if ($target.is('.dropdown.open, .dropup.open')) {
                $target.trigger('hide.bs.dropdown');
            }
            $target.removeClass('open');
            e.stopImmediatePropagation();
        });

    Dropdown.prototype.destroy = function() {
        var globalHandlers = this.data('globalHandlers');
        $('html').off(globalHandlers);
        this.removeData('dropdown');
        this.removeData('globalHandlers');
    };

    $.fn.dropdown = function(option) {
        var optionArgs = _.rest(arguments);
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('dropdown');
            if (!data) {
                $this.data('dropdown', (data = new Dropdown(this)));
            }
            if (typeof option === 'string') {
                data[option].apply($this, optionArgs);
            }
        });
    };

    $.fn.dropdown.Constructor = Dropdown;

    /**
     * Extends Bootstrap.Dropdown to process options passed over data-attribute
     *   <div class="dropdown-menu" data-options="{&quot;container&quot;: true}"> ... </div>
     * to configure additional behavior
     *
     * @param {Object} options
     * @param {string|boolean} options.container specifies selector of container that dropdown-menu have to be attached
     *   on open for floating menu. Or just have boolean value that says - container have to be defined automatically.
     * @param {string} options.align specifies align for floating dropdown-menu,
     *   by default menu left aligned, and this option allows to set 'right' aligning.
     */
    (function() {
        function makeFloating($toggle, $dropdownMenu) {
            $toggle.dropdown('detach', true);
            var $placeholder = $toggle.data('related-data').$placeholder;
            $dropdownMenu
                .addClass('dropdown-menu__floating')
                .one('mouseleave', function(e) {
                    $placeholder.trigger(e.type);
                });
            $toggle.on('mouseleave.floating-dropdown', function(e) {
                if (!$dropdownMenu.is(e.relatedTarget) && !$dropdownMenu.has(e.relatedTarget).length) {
                    $placeholder.trigger(e.type);
                }
            });
        }

        function makeEmbedded($toggle, $dropdownMenu) {
            $dropdownMenu.removeClass('dropdown-menu__floating');
            $toggle.dropdown('detach', false)
                .off('.floating-dropdown');
        }

        function updatePosition($toggle, $dropdownMenu, e) {
            if (e && e.type === 'scroll') {
                var scrollableRect = scrollHelper.getFinalVisibleRect(e.target);
                var dropdownRect = $dropdownMenu[0].getBoundingClientRect();
                var inRange = scrollableRect.top < dropdownRect.top &&
                    scrollableRect.left < dropdownRect.left &&
                    scrollableRect.right > dropdownRect.right &&
                    scrollableRect.bottom > dropdownRect.bottom;
                if ($dropdownMenu.is('.dropdown-menu__floating')) {
                    // floating mode
                    if (!inRange) {
                        makeEmbedded($toggle, $dropdownMenu);
                    } else {
                        $toggle.dropdown('updatePosition');
                    }
                } else {
                    // embedded mode
                    if (inRange) {
                        makeFloating($toggle, $dropdownMenu);
                    }
                }
            } else {
                $toggle.dropdown('updatePosition');
            }
        }

        $(document)
            .on('shown.bs.dropdown', '.dropdown, .dropup', function() {
                var $toggle = $(toggleDropdown, this);
                var $dropdownMenu = $('>.dropdown-menu', this);
                var options = $dropdownMenu.data('options');
                if (options && options.container) {
                    if (options.container === true) {
                        // automatic definition of container selector
                        options.container = 'body, .ui-dialog';
                    }
                    $toggle.data('container', options.container);
                    var handlePositionChange = _.partial(updatePosition, $toggle, $dropdownMenu);
                    $(window).on('resize.floating-dropdown', handlePositionChange);
                    $dropdownMenu.parents().on('scroll.floating-dropdown', handlePositionChange);
                    mediator.on('layout:adjustHeight', handlePositionChange, this);
                    makeFloating($toggle, $dropdownMenu);
                }
            })
            .on('hide.bs.dropdown', '.dropdown.open, .dropup.open', function() {
                var $toggle = $(toggleDropdown, this);
                var $dropdownMenu = $('>.dropdown-menu__placeholder', this).data('related-menu');
                if ($dropdownMenu && $dropdownMenu.length) {
                    makeEmbedded($toggle, $dropdownMenu);
                    mediator.off('layout:adjustHeight', null, this);
                    $dropdownMenu.parents().add(window).off('.floating-dropdown');
                }
            });

        /**
         * Adds handler beforeClearMenus in front of original clearMenus handler
         * (for some reason bindFirst here does not work)
         */
        $(document).on('click.dropdown.data-api', beforeClearMenus);
        var clickEvents = $._data(document, 'events').click;
        var clearMenusHandler = _.find(clickEvents, function(event) {
            return event.handler.name === 'clearMenus';
        });
        clickEvents.splice(clickEvents.indexOf(clearMenusHandler), 0, clickEvents.pop());
    })();
});
