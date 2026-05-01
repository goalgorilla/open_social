<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentRequestBackfillHandler;

/**
 * Unit tests for EventEnrollmentRequestBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentRequestBackfillHandler
 * @group social_event
 */
final class EventEnrollmentRequestBackfillHandlerTest extends EventEnrollmentRequestInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): EventEnrollmentRequestBackfillHandler {
    $plugin_definition = [
      'id' => 'event_enrollment_request',
      'label' => 'Event Enrollment Request',
      'entity_type' => 'event_enrollment',
      'bundle' => 'event_enrollment',
      'handler_service' => 'social_event.eda_event_enrollment_handler',
      'handler_method' => 'eventRequestToJoin',
    ];

    return new EventEnrollmentRequestBackfillHandler(
      [],
      'event_enrollment_request',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->accountSwitcher,
      $this->container
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedStatusValue(): int {
    return EventEnrollmentInterface::REQUEST_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'eventRequestToJoin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'event_enrollment_request';
  }

}
