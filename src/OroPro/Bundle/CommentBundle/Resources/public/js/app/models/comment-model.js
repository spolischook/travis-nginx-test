/*global define*/
define(function (require) {
    'use strict';

    var CommentModel,
        PreviousCommentModel = require('orocomment/js/app/models/comment-model-previous');

    CommentModel = PreviousCommentModel.extend({
        /**
         * @inheritDoc
         */
        url: function () {
            var url, _sa_org_id;

            url = CommentModel.__super__.url.call(this, arguments);

            // Need to add organization id to url
            _sa_org_id = $('input#_sa_org_id').val();
            if (_sa_org_id) {
                url = url + (url.indexOf('?') == -1 ? '?' : '&') + '_sa_org_id=' + _sa_org_id;
            }

            return url;
        }
    });

    return CommentModel;
});
