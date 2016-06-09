/**
 * @file
 * Attaches entity-type selection behaviors to the widget form.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.dynamicEntityReferenceWidget = {
    attach: function (context) {
      drupalSettings.dynamic_entity_reference = drupalSettings.dynamic_entity_reference || {};
      function dynamicEntityReferenceWidgetSelect(e) {
        var data = e.data;
        var $select = $('.' + data.select);
        var $autocomplete = $select.parents('.container-inline').find('.form-autocomplete');
        var entityTypeId = $select.val();
        $autocomplete.attr('data-autocomplete-path', drupalSettings.dynamic_entity_reference[data.select][entityTypeId]);
      }
      Object.keys(drupalSettings.dynamic_entity_reference).forEach(function (field_class) {
        $(context)
          .find('.' + field_class)
          .once('dynamic-entity-reference')
          .on('change', {select: field_class}, dynamicEntityReferenceWidgetSelect);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
