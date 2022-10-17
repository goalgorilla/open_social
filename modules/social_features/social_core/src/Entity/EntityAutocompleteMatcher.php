<?php

namespace Drupal\social_core\Entity;

use Drupal\Core\Entity\EntityAutocompleteMatcher as EntityAutocompleteMatcherBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Html;

/**
 * Class EntityAutocompleteMatcher.
 *
 * @package Drupal\social_core\Entity
 */
class EntityAutocompleteMatcher extends EntityAutocompleteMatcherBase {

  /**
   * {@inheritdoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    // Get an array of matching entities.
    $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
    $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

    // Loop through the entities and convert them into autocomplete output.
    foreach ($entity_labels as $values) {
      foreach ($values as $entity_id => $label) {
        // Skip certain entity_id's that are already a member or a enrollee.
        // We can just add this to our render arrays from now on.
        // '#selection_settings' => [ 'skip_entity' => ['7', '8', '9'] ].
        if (!empty($selection_settings['skip_entity']) && in_array($entity_id, $selection_settings['skip_entity'], FALSE)) {
          continue;
        }

        $key = !empty($selection_settings['hide_id']) ? $label : "$label ($entity_id)";
        // Strip things like starting/trailing white spaces, line breaks and
        // tags.
        $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
        // Names containing commas or quotes must be wrapped in quotes.
        $key = Tags::encode($key);
        $matches[] = ['value' => $key, 'label' => $label];
      }
    }

    return $matches;
  }

}
