define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    return function(options) {
        var $source = options._sourceElement;
        var $server = $('input[id*="server_value"]');
        var $version = $('select[id*="version_value"]');
        var $login = $('input[id*="login_value"]');
        var $password = $('input[id*="password_value"]');
        var $domainList = $('input[id*="domain_list_value"]');
        var $btn = $source.find('button');
        var $status = $source.find('.connection-status');
        var mediator = require('oroui/js/mediator');

        var onError = function(message) {
            message = message || __('oropro.ews.integration_transport.connection.check.error');
            $status.removeClass('alert-info')
                .addClass('alert-error')
                .html(message);
        };

        $btn.on('click', function() {
            mediator.execute('showLoading');
            $.getJSON(
                options.pingUrl,
                {
                    'server': $server.val(),
                    'version': $version.val(),
                    'login': $login.val(),
                    'password': $password.val(),
                    'domain_list': $domainList.val()
                },
                function(response) {
                    mediator.execute('hideLoading');
                    if (_.isUndefined(response.error)) {
                        $status.removeClass('alert-error')
                            .addClass('alert-info')
                            .html(response.msg);
                    } else {
                        onError(response.error);
                    }
                }
            ).always(
                function() {
                    $status.show();
                }
            ).fail(
                onError
            );
        });
    };
});
