/**
 * @file
 * Block behaviors.
 */

(function ($, window) {

    'use strict';

    /**
     * Provide the summary information for the block settings vertical tabs.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior for the block settings summaries.
     */
    Drupal.behaviors.blockSettingsSummaryGroup = {
        attach: function () {
            // The drupalSetSummary method required for this behavior is not available
            // on the Blocks administration page, so we need to make sure this
            // behavior is processed only if drupalSetSummary is defined.
            if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
                return;
            }

            /**
             * Create a summary for checkboxes in the provided context.
             *
             * @param {HTMLDocument|HTMLElement} context
             *   A context where one would find checkboxes to summarize.
             *
             * @return {string}
             *   A string with the summary.
             */
            function checkboxesSummary(context) {
                var vals = [];
                var $checkboxes = $(context).find('input[type="checkbox"]:checked + label');
                var il = $checkboxes.length;
                for (var i = 0; i < il; i++) {
                    vals.push($($checkboxes[i]).html());
                }
                if (!vals.length) {
                    vals.push(Drupal.t('Not restricted'));
                }
                return vals.join(', ');
            }

            $('[data-drupal-selector="edit-visibility-group-type"]').drupalSetSummary(checkboxesSummary);
        }
    };

})(jQuery, window);
