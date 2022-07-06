<?php

namespace Drupal\social_core\Entity;

use Drupal\select2\EntityAutocompleteMatcher as EntityAutocompleteMatcherBase;
use Drupal\Component\Utility\Html;
use Drupal\social_user\VerifyableUserInterface;
use Drupal\user\Entity\User;

/**
 * Class Select2EntityAutocompleteMatcher.
 *
 * @package Drupal\social_core\Entity
 */
class Select2EntityAutocompleteMatcher extends EntityAutocompleteMatcherBase {

  /**
   * {@inheritdoc}
   */
  public function getMatches($target_type, $selection_handler, array $selection_settings, $string = '', array $selected = []) {
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    // Get an array of matching entities.
    $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
    $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
    $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit);

    // Loop through the entities and convert them into autocomplete output.
    foreach ($entity_labels as $values) {
      foreach ($values as $entity_id => $label) {
        // Skip certain entity_id's that are already a member or a enrollee.
        // We can just add this to our render arrays from now on.
        // '#selection_settings' => [ 'skip_entity' => ['7', '8', '9'] ].
        if (!empty($selection_settings['skip_entity']) && in_array($entity_id, $selection_settings['skip_entity'], FALSE)) {
          continue;
        }

        // Ensure that we are able to select Verified+ users only.
        if ($target_type === 'user' && $selection_handler === 'social') {
          /** @var \Drupal\user\UserInterface|NULL $account */
          $account = User::load($entity_id);
          if ($account instanceof VerifyableUserInterface && !$account->isVerified()) {
            continue;
          }
        }

        $label = !empty($selection_settings['hide_id']) ? $label : "$label ($entity_id)";

        $matches[$entity_id] = ['id' => $entity_id, 'text' => Html::decodeEntities($label)];
      }
    }

    $this->moduleHandler->alter('select2_autocomplete_matches', $matches, $options);

    return array_values($matches);
  }

}
