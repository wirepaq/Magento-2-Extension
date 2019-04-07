/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */

define([
    'jquery',
    'underscore',
    'Unbxd_ProductFeed/js/action/request',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (
    $,
    _,
    managerAction,
    alert,
    confirm,
    $t
) {
    'use strict';

    $.widget('custom.unbxdManager', {
        options: {
            config: {},
            formKey: '',
            triggers: {
                checkCron: 'unbxd_check_cron',
                fullSync: 'unbxd_full_sync',
                incrementalSync: 'unbxd_incremental_sync',
            },
            params: {
                'isAjax': true
            }
        },

        /**
         * Initializes
         *
         * @returns {exports}
         */
        initialize: function () {
            var self = this;

            this._super();
            managerAction.registerCallback(function (data) {

            });

            return this;
        },

        /**
         * @private
         */
        _create: function () {
            $('#' + this.options.triggers.checkCron)
                .on('click', $.proxy(this._checkCron, this));
            $('#' + this.options.triggers.fullSync)
                .on('click', $.proxy(this._full, this));
            $('#' + this.options.triggers.incrementalSync)
                .on('click', $.proxy(this._incremental, this));
        },

        /**
         * Retrieve cron jobs
         *
         * @param event
         * @returns {boolean}
         * @private
         */
        _checkCron: function (event) {
            var self = this,
                target = $(event.currentTarget),
                actionUrl = self.options.config.url.cronJobs,
                params = {
                    'form_key': this.formKey
                };

            $.extend(params, self.options.params);
            managerAction(actionUrl, 'POST', params);

            return true;
        },

        /**
         * @param event
         * @returns {boolean}
         * @private
         */
        _full: function (event) {
            var self = this,
                target = $(event.currentTarget),
                isActionAllow = self.options.config.isActionAllow,
                isCronConfigured = self.options.config.isCronConfigured,
                actionUrl = self.options.config.url.fullSync,
                params = {
                    'form_key': this.formKey
                };

            if (!isActionAllow) {
                alert({
                    content: $.mage.__('Please provide authorization keys to perform this operation.'),
                });

                return false;
            }

            if (isCronConfigured) {
                alert({
                    content: $.mage.__('This process already configured by related cron job.' + '<br/>' +
                        'To prevent synchronization conflicts, please disable related cron job ' +
                        'before run this action.'),
                });

                return false;
            }

            confirm({
                title: $.mage.__('Confirmation'),
                content: $.mage.__('Are you sure do you want to run synchronization with ' +
                    '<a href="http://unbxd.com"><strong>Unbxd</strong></a> service?' + '<br/><br/>' +
                    '<strong>' + 'NOTE: ' +  '</strong>' + 'Recommended to configure related cron job for this action.'),
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        $.extend(params, self.options.params);
                        managerAction(actionUrl, 'POST', params);
                    },

                    /** @inheritdoc */
                    always: function (e) {
                        e.stopImmediatePropagation();
                    }
                }
            });

            return false;
        },

        /**
         * @param event
         * @private
         */
        _incremental: function (event) {
            
        }
    });

    return $.custom.unbxdManager;
});
