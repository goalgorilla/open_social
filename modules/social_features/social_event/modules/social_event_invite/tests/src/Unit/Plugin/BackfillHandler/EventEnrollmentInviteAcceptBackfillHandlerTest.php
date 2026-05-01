<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event_invite\Unit\Plugin\BackfillHandler;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_invite\Plugin\BackfillHandler\EventEnrollmentInviteAcceptBackfillHandler;
use Drupal\Tests\social_event\Unit\Plugin\BackfillHandler\EventEnrollmentRequestInviteBackfillHandlerTestBase;

/**
 * Unit tests for EventEnrollmentInviteAcceptBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event_invite\Plugin\BackfillHandler\EventEnrollmentInviteAcceptBackfillHandler
 * @group social_event_invite
 */
final class EventEnrollmentInviteAcceptBackfillHandlerTest extends EventEnrollmentRequestInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): EventEnrollmentInviteAcceptBackfillHandler {
    $plugin_definition = [
      'id' => 'event_enrollment_invite_accept',
      'label' => 'Event Enrollment Invite Accept',
      'entity_type' => 'event_enrollment',
      'bundle' => 'event_enrollment',
      'handler_service' => 'social_event.eda_event_enrollment_handler',
      'handler_method' => 'eventInviteToJoinAccepted',
    ];

    return new EventEnrollmentInviteAcceptBackfillHandler(
      [],
      'event_enrollment_invite_accept',
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
    return EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'eventInviteToJoinAccepted';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'event_enrollment_invite_accept';
  }

}
