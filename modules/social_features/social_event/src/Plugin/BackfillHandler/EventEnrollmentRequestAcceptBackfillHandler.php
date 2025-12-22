<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\user\UserInterface;

/**
 * Backfill handler for event enrollment request entities with approved status.
 *
 * @BackfillHandler(
 *   id = "event_enrollment_request_accept",
 *   label = @Translation("Event Enrollment Request Accept"),
 *   entity_type = "event_enrollment",
 *   bundle = "event_enrollment",
 *   handler_service = "social_event.eda_event_enrollment_handler",
 *   handler_method = "eventRequestToJoinAccepted"
 * )
 */
final class EventEnrollmentRequestAcceptBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by joining status for enrollments with approved request status.
    $query->condition('field_request_or_invite_status', EventEnrollmentInterface::REQUEST_APPROVED);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
    // For request accept, the actor is the user who approved (stored in owner).
    // The owner is set to the approver in UpdateEnrollRequestController.
    if ($entity instanceof EventEnrollmentInterface) {
      $owner = $entity->getOwner();
      return !$owner->isAnonymous() ? $owner : NULL;
    }
    return parent::getActorFromEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): void {
    if (!$entity instanceof EventEnrollmentInterface) {
      throw new \InvalidArgumentException(sprintf(
        'Expected EventEnrollmentInterface, got %s',
        get_class($entity)
      ));
    }

    parent::process($entity);
  }

}
