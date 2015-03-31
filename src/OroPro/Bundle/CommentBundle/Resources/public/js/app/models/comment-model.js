/*global define*/
define(function (require) {
    'use strict';

    var ProCommentModel,
        CommentModel = require('orocomment/js/app/models/comment-model');

    ProCommentModel = CommentModel.extend({
        /**
         * @inheritDoc
         */
        url: function () {
            var url, _sa_org_id;

            url = ProCommentModel.__super__.url.call(this, arguments);

            // Need to add organization id to url
            _sa_org_id = $('input#_sa_org_id').val();
            if (_sa_org_id) {
                url = url + (url.indexOf('?') == -1 ? '?' : '&') + '_sa_org_id=' + _sa_org_id;
            }

            return url;
        }
    });

    return ProCommentModel;
});
