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
                }
            });
        }
    };

})(jQuery, Backbone, Drupal, document);
