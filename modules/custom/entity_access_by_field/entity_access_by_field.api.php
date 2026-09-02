<?php

/**
 * @file
 * Hooks provided by the Entity Access By Field module.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the node access this module grants or denies.
 *
 * @param \Drupal\Core\Access\AccessResultInterface $access
 *   The access result to alter.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The node the access check is running for.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account the access check is running for.
 *
 * @see entity_access_by_field_node_access()
 */
function hook_entity_access_by_field_node_access_alter(
  AccessResultInterface &$access,
  EntityInterface $entity,
  AccountInterface $account,
): void {
  // Unpublished content is only readable by its author, which locks out the
  // co-authors that work on it together with them.
  if (!$access->isForbidden()) {
    return;
  }

  foreach ($entity->get('field_my_module_coauthors')->referencedEntities() as $coauthor) {
    if ($coauthor->id() === $account->id()) {
      $access = AccessResult::allowed()
        ->addCacheContexts(['user'])
        ->addCacheableDependency($entity)
        ->addCacheableDependency($access);
      return;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
