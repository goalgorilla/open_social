<?php

namespace Drupal\mentions;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Mentions entity.
 *
 * @see \Drupal\mentions\Entity\Mentions.
 */
class MentionsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
    switch ($operation) {
      case 'view':
        // @TODO: Add some permissions.
        return AccessResult::allowed();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }
}
