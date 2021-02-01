<?php

namespace Drupal\social_group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the group entity type.
 *
 * @EntityReferenceSelection(
 *   id = "social:group",
 *   label = @Translation("Group selection"),
 *   group = "social",
 *   entity_types = {"group"},
 *   weight = 0
 * )
 */
class SocialGroupSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    $configuration = $this->getConfiguration();
    $all_group_types = [];

    if (is_array($configuration['target_bundles'])) {
      if ($configuration['target_bundles'] === []) {
        return $query;
      }
      else {
        $all_group_types = $configuration['target_bundles'];
      }
    }

    $plugin_id = 'group_node:' . $configuration['entity']->bundle();
    $storage = $this->entityTypeManager->getStorage('group_type');

    if (!$all_group_types) {
      $all_group_types = $storage->getQuery()->execute();
    }

    $excluded_group_types = [];

    foreach ($all_group_types as $group_type_id) {
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $group_type = $storage->load($group_type_id);

      if (!$group_type->hasContentPlugin($plugin_id)) {
        $excluded_group_types[] = $group_type_id;
      }
    }

    if ($excluded_group_types) {
      $query->condition(
        $this->entityTypeManager->getDefinition($configuration['target_type'])->getKey('bundle'),
        array_diff($all_group_types, $excluded_group_types),
        'IN'
      );
    }

    return $query;
  }

}
