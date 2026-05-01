<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentRequestAcceptBackfillHandler;

/**
 * Unit tests for EventEnrollmentRequestAcceptBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentRequestAcceptBackfillHandler
 * @group social_event
 */
final class EventEnrollmentRequestAcceptBackfillHandlerTest extends EventEnrollmentRequestInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): EventEnrollmentRequestAcceptBackfillHandler {
    $plugin_definition = [
      'id' => 'event_enrollment_request_accept',
      'label' => 'Event Enrollment Request Accept',
      'entity_type' => 'event_enrollment',
      'bundle' => 'event_enrollment',
      'handler_service' => 'social_event.eda_event_enrollment_handler',
      'handler_method' => 'eventRequestToJoinAccepted',
    ];

    return new EventEnrollmentRequestAcceptBackfillHandler(
      [],
      'event_enrollment_request_accept',
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
    return EventEnrollmentInterface::REQUEST_APPROVED;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'eventRequestToJoinAccepted';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'event_enrollment_request_accept';
  }

}
