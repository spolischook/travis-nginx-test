define(function(require) {
    'use strict';

    var UpdatePageView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    UpdatePageView = BaseView.extend({

        initialize: function(options) {
            UpdatePageView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);

            this.render();
        },

        render: function() {
            this.initLayout().then(_.bind(this.afterLayoutInit, this));
        },

        afterLayoutInit: function() {
            var customer = this.pageComponent('orocrm_sales_opportunity_form_customer').$sourceElement;
            var channel = this.pageComponent('orocrm_sales_opportunity_form_dataChannel').view.$el;
            var status = this.pageComponent('orocrm_sales_opportunity_form_status').view.$el;
            var probability = this.$('input[data-name="field__probability"]:enabled');
            var probabilities = status.data('probabilities');
            var shouldChangeProbability = false;

            customer.on('change', function(e) {
                if (e.added && e.added['dataChannel.id']) {
                    channel.val(e.added['dataChannel.id']).trigger('change.select2');
                }
            });

            channel.on('change', function(e) {
                var value = customer.val();
                if (value) {
                    value = JSON.parse(value);
                    if (value.id !== null) {
                        // Reset customer select2 in case when the different channel selected
                        // Does not reset customer in case when he is not created yet
                        customer.val('').trigger('change.select2');
                    }
                }
            });

            // probability field might be missing or disabled
            if (0 === probability.length) {
                return;
            }

            if (probabilities.hasOwnProperty(status.val())) {
                if (probabilities[status.val()] == probability.val() / 100) {
                    shouldChangeProbability = true;
                }
            }

            probability.on('change', function(e) {
                shouldChangeProbability = false;
            });

            status.on('change', function(e) {
                var val = status.val();
                var defaultProbability;

                if (!shouldChangeProbability) {
                    return;
                }

                if (probabilities.hasOwnProperty(val)) {
                    defaultProbability = probabilities[val] * 100;
                    probability.val(defaultProbability);
                }
            });
        }
    });

    return UpdatePageView;
});
