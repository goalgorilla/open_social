<?php

namespace Drupal\social_comment\Entity\Access;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;
use Drupal\user\EntityOwnerInterface;

/**
 * Controls query access for comment entities.
 *
 * @see \Drupal\entity\QueryAccess\QueryAccessHandler
 */
class CommentQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $has_owner = $this->entityType->entityClassImplements(EntityOwnerInterface::class);
    $has_published = $this->entityType->entityClassImplements(EntityPublishedInterface::class);
    // Guard against broken/incomplete entity type definitions.
    if ($has_owner && !$this->entityType->hasKey('owner')) {
      throw new \RuntimeException(sprintf('The "%s" entity type did not define a "owner" key.', $entity_type_id));
    }
    if ($has_published && !$this->entityType->hasKey('published')) {
      throw new \RuntimeException(sprintf('The "%s" entity type did not define a "published" key', $entity_type_id));
    }

    if ($account->hasPermission("administer comments")) {
      // The user has full access to all operations, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    if ($has_owner) {
      $entity_conditions = $this->buildEntityOwnerConditions($operation, $account);
    }
    else {
      $entity_conditions = $this->buildEntityConditions($operation, $account);
    }

    $conditions = NULL;
    if ($operation == 'view' && $has_published) {
      $published_key = $this->entityType->getKey('published');
      $published_conditions = NULL;

      if ($entity_conditions) {
        // Restrict the existing conditions to published entities only.
        $published_conditions = new ConditionGroup('AND');
        $published_conditions->addCacheContexts(['user.permissions']);
        $published_conditions->addCondition($entity_conditions);
        $published_conditions->addCondition($published_key, '1');
      }

      if ($published_conditions) {
        $conditions = $published_conditions;
      }
    }
    else {
      $conditions = $entity_conditions;
    }

    if (!$conditions) {
      // The user doesn't have access to any entities.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->alwaysFalse();
    }

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityOwnerConditions($operation, AccountInterface $account) {
    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    if ($account->hasPermission("access comments")) {
      // The user has full access, no conditions needed.
      return $conditions;
    }

    return $conditions->count() ? $conditions : NULL;
  }

}
