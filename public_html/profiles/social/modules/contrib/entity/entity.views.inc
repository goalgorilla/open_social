<?php

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Implements hook_views_data().
 */
function entity_views_data() {
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  $entity_types = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
    return $entity_type->isSubclassOf(ContentEntityInterface::class);
  });

  $data = [];
  foreach ($entity_types as $entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $base_table = $entity_type->getBaseTable() ?: $entity_type->id();

    if ($entity_type->hasViewBuilderClass()) {
      $data[$base_table]['rendered_entity'] = [
        'field' => [
          'title' => t('Rendered entity'),
          'help' => t('Renders an entity in a view mode.'),
          'id' => 'rendered_entity',
        ],
      ];
    }
  }

  return $data;
}
