/*jshint -W098*/
var ORO = (function(ORO) {
    'use strict';

    ORO.EmbedForm = function(options) {
        var formContainer = document.getElementById(options.container);
        var iframe = document.createElement('iframe');
        iframe.src = options.iframe.src;
        iframe.width = options.iframe.width;
        iframe.height = options.iframe.height;
        iframe.frameBorder = options.iframe.frameBorder;

        formContainer.appendChild(iframe);
    };

    return ORO;
})(ORO || {});
