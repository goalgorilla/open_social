<?php

namespace Drupal\social_event;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event enrollment entity.
 *
 * @see \Drupal\social_event\Entity\EventEnrollment.
 */
class EventEnrollmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished event enrollment entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published event enrollment entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit event enrollment entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete event enrollment entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add event enrollment entities');
  }

}
