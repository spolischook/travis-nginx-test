define(function(require) {
    'use strict';

    var ProCommentModel;
    var $ = require('jquery');
    var CommentModel = require('orocomment/js/app/models/comment-model');

    ProCommentModel = CommentModel.extend({
        /**
         * @inheritDoc
         */
        url: function() {
            var url = ProCommentModel.__super__.url.call(this, arguments);

            // Need to add organization id to url
            var saOrgId = $('input#_sa_org_id').val();
            if (saOrgId) {
                url = url + (url.indexOf('?') === -1 ? '?' : '&') + '_sa_org_id=' + saOrgId;
            }

            return url;
        }
    });

    return ProCommentModel;
});
