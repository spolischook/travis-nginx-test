/* global require */
require(
    ['jquery'],
    function ($) {
        'use strict';
        $(function() {
            var all_selector = $('input.all-selector:checkbox');
            var selective_selectors = $('div.selective-selector input:checkbox');

            if ($(all_selector).prop('checked') === true) {
                $(selective_selectors).each(function(index, el){
                    $(el).prop('disabled', true);
                });
            }
            $(document).on('change', 'input.all-selector:checkbox', function () {
                if($(this).prop('checked') === true) {
                    $(selective_selectors).each(function(index, el){
                        $(el).prop('disabled', true);
                    });
                } else {
                    $(selective_selectors).each(function(index, el){
                        $(el).removeAttr('disabled');
                    });
                }
            });
        });
    }
);
