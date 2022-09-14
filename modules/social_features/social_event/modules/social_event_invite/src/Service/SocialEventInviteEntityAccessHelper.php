<?php

namespace Drupal\social_event_invite\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_access_by_field\EntityAccessHelper;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Helper class for checking entity access.
 */
class SocialEventInviteEntityAccessHelper extends EntityAccessHelper {

  /**
   * {@inheritdoc}
   */
  protected function process(
    EntityInterface $entity,
    string $operation,
    AccountInterface $account
  ): int {
    // If a person got invited then allow access to view the node.
    if (
      $entity->getEntityTypeId() !== 'node' ||
      $entity->isNew() ||
      $operation !== 'view'
    ) {
      return parent::process($entity, $operation, $account);
    }

    $storage = $this->entityTypeManager->getStorage('event_enrollment');

    $ids = $storage->getQuery()
      ->accessCheck()
      ->condition('field_account', $account->id())
      ->condition('field_event', $entity->id())
      ->range(0, 1)
      ->execute();

    if (
      !empty($ids) &&
      ($enrollment = $storage->load(reset($ids))) !== NULL
    ) {
      $status = (int) $enrollment->field_request_or_invite_status->value;

      if (
        $status !== EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED &&
        $status !== EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED
      ) {
        return self::ALLOW;
      }
    }

    return parent::process($entity, $operation, $account);
  }

}
