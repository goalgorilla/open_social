<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event_invite\Unit\Plugin\BackfillHandler;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_invite\Plugin\BackfillHandler\EventEnrollmentInviteBackfillHandler;
use Drupal\Tests\social_event\Unit\Plugin\BackfillHandler\EventEnrollmentRequestInviteBackfillHandlerTestBase;

/**
 * Unit tests for EventEnrollmentInviteBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event_invite\Plugin\BackfillHandler\EventEnrollmentInviteBackfillHandler
 * @group social_event_invite
 */
final class EventEnrollmentInviteBackfillHandlerTest extends EventEnrollmentRequestInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): EventEnrollmentInviteBackfillHandler {
    $plugin_definition = [
      'id' => 'event_enrollment_invite',
      'label' => 'Event Enrollment Invite',
      'entity_type' => 'event_enrollment',
      'bundle' => 'event_enrollment',
      'handler_service' => 'social_event.eda_event_enrollment_handler',
      'handler_method' => 'eventInviteToJoin',
    ];

    return new EventEnrollmentInviteBackfillHandler(
      [],
      'event_enrollment_invite',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedStatusValue(): int {
    return EventEnrollmentInterface::INVITE_PENDING_REPLY;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'eventInviteToJoin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'event_enrollment_invite';
  }

}
