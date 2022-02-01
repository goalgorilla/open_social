<?php

namespace Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPlugin;

use Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginBase;
use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Provides a 'EntityTypeCount' share usage data plugin.
 *
 * @ShareUsageDataPlugin(
 *  id = "entity_type_count",
 *  label = @Translation("Entity Type Count"),
 *  setting = "entity_type_count",
 *  weight = -440,
 * )
 */
class EntityTypeCount extends ShareUsageDataPluginBase {

  /**
   * Get the value.
   *
   * @return array
   *   $json array.
   */
  public function getValue() {
    $value = [];
    $definitions = $this->entityTypeManager->getDefinitions();

    foreach ($definitions as $definition) {
      $entity_type_id = $definition->id();
      // We don't need to add config entities.
      if ($definition instanceof ConfigEntityType) {
        continue;
      }

      $entity_bundle_info = \Drupal::service('entity_type.bundle.info');
      $bundle_info = $entity_bundle_info->getBundleInfo($entity_type_id);
      $bundles = [];

      if (count($bundle_info) > 1) {
        foreach ($bundle_info as $bundle_id => $bundle_data) {
          // Suppress warning about unused variable.
          unset($bundle_data);
          $keys = $definition->getKeys();
          $bundle_key = $keys['bundle'] ?? 'bundle';

          $query = \Drupal::entityQuery($entity_type_id);
          $query->condition($bundle_key, $bundle_id);
          $query->count();
          $count = $query->execute();

          $row = [
            'bundle' => $bundle_id,
            'count' => $count,
          ];

          $bundles[] = $row;
        }
      }

      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getAggregateQuery();
      $query->count();
      $count = $query->execute();

      $entity = [
        'entity_type' => $entity_type_id,
        'count' => $count,
        'bundles' => $bundles,
      ];

      $value[$entity_type_id] = $entity;
    }

    return $value;
  }

}
