<?php

namespace Drupal\address\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides en entity reference selection plugin for zones.
 *
 * @EntityReferenceSelection(
 *   id = "default:zone",
 *   label = @Translation("Zone selection"),
 *   entity_types = {"zone"},
 *   group = "default",
 *   weight = 1
 * )
 */
class ZoneSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // The 'zone' zone member needs to be able to exclude the parent zone
    // from selection. It does this by passing a custom skip_id parameter
    // to the entity_autocomplete form element via #handler_settings.
    $handler_settings = $this->configuration['handler_settings'];
    if (!empty($handler_settings['skip_id'])) {
      $query->condition('id', $handler_settings['skip_id'], '<>');
    }

    return $query;
  }

}
