<?php

namespace Drupal\social_group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityInterface;
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

    if (is_array($configuration['target_bundles'])
    && !empty($configuration['target_bundles'])) {
      $plugin_id = 'group_node:' . $configuration['entity']->bundle();
      $storage = $this->entityTypeManager->getStorage('group_type');
      $all_group_types = $configuration['target_bundles'];
      $excluded_group_types = [];
      foreach ($all_group_types as $group_type_id) {
        /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
        $group_type = $storage->load($group_type_id);

        if (!$group_type->hasContentPlugin($plugin_id)) {
          $excluded_group_types[] = $group_type_id;
        }
      }

      if (!empty($excluded_group_types)) {
        $diff = array_diff($all_group_types, $excluded_group_types);
        if (!empty($diff)) {
          $entity_type = $this->entityTypeManager->getDefinition($configuration['target_type']);
          if ($entity_type instanceof EntityInterface) {
            $bundle = $entity_type->getKey('bundle');
            if (is_string($bundle)) {
              $query->condition(
                $bundle,
                $diff,
                'IN'
              );
            }
          }
        }
      }
    }

    return $query;
  }

}
