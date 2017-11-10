<?php

namespace Drupal\social_core\Entity\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete as EntityAutocompleteBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("entity_autocomplete")
 */
class EntityAutocomplete extends EntityAutocompleteBase {

  /**
   * {@inheritdoc}
   */
  public static function getEntityLabels(array $entities, $hide_id = FALSE) {
    $entity_labels = [];

    foreach ($entities as $entity) {
      // Use the special view label, since some entities allow the label to be
      // viewed, even if the entity is not allowed to be viewed.
      $label = ($entity->access('view label')) ? $entity->label() : t('- Restricted access -');

      // Take into account "autocreated" entities.
      if (!$entity->isNew() && !$hide_id) {
        $label .= ' (' . $entity->id() . ')';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }

    return implode(', ', $entity_labels);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $hide_id = !empty($element['#selection_settings']['hide_id']);

    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#process_default_value']) {
      if (is_array($element['#default_value']) && $element['#tags'] !== TRUE) {
        throw new \InvalidArgumentException('The #default_value property is an array but the form element does not allow multiple values.');
      }
      elseif (!empty($element['#default_value']) && !is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = [$element['#default_value']];
      }

      if ($element['#default_value']) {
        if (!(reset($element['#default_value']) instanceof EntityInterface)) {
          throw new \InvalidArgumentException('The #default_value property has to be an entity object or an array of entity objects.');
        }

        // Extract the labels from the passed-in entity objects, taking access
        // checks into account.
        return static::getEntityLabels($element['#default_value'], $hide_id);
      }
    }

    // Potentially the #value is set directly, so it contains the 'target_id'
    // array structure instead of a string.
    if ($input !== FALSE && is_array($input)) {
      $entity_ids = array_map(function (array $item) {
        return $item['target_id'];
      }, $input);

      $entities = \Drupal::entityTypeManager()->getStorage($element['#target_type'])->loadMultiple($entity_ids);

      return static::getEntityLabels($entities, $hide_id);
    }
  }

}
