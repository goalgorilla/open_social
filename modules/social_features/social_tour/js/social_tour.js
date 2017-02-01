/**
 * @file
 * Attaches behaviors for the Tour module's toolbar tab.
 */

(function ($, Backbone, Drupal, document) {

    'use strict';

    /**
     * Attaches the tour's toolbar tab behavior on document load, heavily relies on tour.js
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attach tour functionality on `tour` events.
     */
    Drupal.behaviors.social_tour = {
        attach: function (context) {
            $('body').once('social_tour').each(function () {
                var model = new Drupal.tour.models.StateModel();
                new Drupal.tour.views.ToggleTourView({
                    el: $(context).find('#toolbar-tab-tour'),
                    model: model
                });

                model
                    // Allow other scripts to respond to tour events.
                    .on('change:isActive', function (model, isActive) {
                        $(document).trigger((isActive) ? 'drupalTourStarted' : 'drupalTourStopped');
                    })
                    // Initialization: check whether a tour is available on the current
                    // page.
                    .set('tour', $(context).find('ol#tour'));

                // Start the tour immediately if it's available.
                if ($(context).find('ol#tour').length > 0 && model.isActive !== true) {
                    model.set('isActive', true);

                    // Alter the tour button templates.
                    $('.button.button--primary', '.tip-module-social-tour').each(function(){
                        $(this).removeClass('button').addClass('btn');
                        $(this).removeClass('button--primary').addClass('btn-primary');
                    })

                    // For our social tour, we only want to show the next button if there is more than 1 tool tip.
                    if ($(context).find('.joyride-tip-guide.tip-module-social-tour').length <= 1) {
                        $('.joyride-tip-guide.tip-module-social-tour .joyride-content-wrapper a.joyride-next-tip').hide();
                    }
                }
            });

            // If we click somewhere in our document window, if it's not the jQuery modal container.
            // Or one of it's descendants. We hide the modal background and the tour tip.
            $(document).click(function(e) {
                var container = $(".joyride-tip-guide.tip-module-social-tour");

                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    $(".joyride-tip-guide.tip-module-social-tour").fadeOut("fast");
                    if ($(".joyride-modal-bg").length > 0) {
                        $(".joyride-modal-bg").remove();
                    }
                }
            })
        }
    };

})(jQuery, Backbone, Drupal, document);
