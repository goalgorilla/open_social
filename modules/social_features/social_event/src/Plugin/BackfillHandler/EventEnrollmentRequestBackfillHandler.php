<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for event enrollment request entities with pending status.
 *
 * @BackfillHandler(
 *   id = "event_enrollment_request",
 *   label = @Translation("Event Enrollment Request"),
 *   entity_type = "event_enrollment",
 *   bundle = "event_enrollment",
 *   handler_service = "social_event.eda_event_enrollment_handler",
 *   handler_method = "eventRequestToJoin"
 * )
 */
final class EventEnrollmentRequestBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by joining status for enrollments with pending request status.
    $query->condition('field_request_or_invite_status', EventEnrollmentInterface::REQUEST_PENDING);
    return $query;
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
