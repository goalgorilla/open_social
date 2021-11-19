<?php

/**
 * @file
 * Hooks provided by the Social Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;

/**
 * Provides a method to alter query access conditions for comments.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account to check access for.
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type.
 * @param \Drupal\entity\QueryAccess\ConditionGroup|null $conditions
 *   The access conditions or NULL.
 *
 * @ingroup social_comment_api
 */
function hook_social_comment_query_access_alter(AccountInterface $account, EntityTypeInterface $entity_type, ConditionGroup &$conditions = NULL) {
  $conditions = new ConditionGroup('OR');
  $conditions->addCacheContexts(['user.permissions']);
  $conditions->alwaysFalse();
}

/**
 * @} End of "addtogroup hooks".
 */
