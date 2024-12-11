<?php

namespace Drupal\social_group\Hooks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Alter;

/**
 * HUX class for Operation hooks related with Group.
 */
final class SocialGroupOperationHooks {

  /**
   * Remove unused operation on group pages.
   *
   * @see \hook_entity_operation_alter()
   */
  #[Alter('entity_operation')]
  public function removeUnusedOperations(array &$operations, EntityInterface $entity): void {
    // The operations should be removed only for Groups.
    if ($entity->getEntityTypeId() !== 'group') {
      return;
    }

    // Operations to be removed.
    $exclude_operations = [
      'nodes',
      'media',
    ];

    $operations = array_filter($operations, function ($key) use ($exclude_operations) {
      return !in_array($key, $exclude_operations);
    }, ARRAY_FILTER_USE_KEY);
  }

}
