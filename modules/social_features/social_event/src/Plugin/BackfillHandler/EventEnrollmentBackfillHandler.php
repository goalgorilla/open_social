<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for event enrollment entities.
 *
 * @BackfillHandler(
 *   id = "event_enrollment",
 *   label = @Translation("Event Enrollment"),
 *   entity_type = "event_enrollment",
 *   bundle = "event_enrollment",
 *   handler_service = "social_event.eda_event_enrollment_handler",
 *   handler_method = "eventEnrollmentCreate"
 * )
 */
final class EventEnrollmentBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Adds filter by field_enrollment_status to only get enrolled enrollments
   * (STATUS_ENROLLED = '1').
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by enrollment status to only get enrolled enrollments.
    $query->condition('field_enrollment_status', EventEnrollmentInterface::STATUS_ENROLLED);
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
