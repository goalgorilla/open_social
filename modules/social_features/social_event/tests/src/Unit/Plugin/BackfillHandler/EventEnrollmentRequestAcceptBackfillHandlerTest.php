<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentRequestAcceptBackfillHandler;
use Drupal\user\UserInterface;

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

  /**
   * Tests process() completes when enrollment owner reference is missing.
   *
   * @covers ::process
   * @covers ::getActorFromEntity
   */
  public function testProcessLeavesCurrentUserUnchangedWhenOwnerIsNull(): void {
    $enrollment = $this->createMock(EventEnrollmentInterface::class);
    $enrollment->method('getOwner')->willReturn(NULL);
    $this->accountSwitcher->expects($this->never())->method('switchTo');

    $eda_handler = new class() {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * Test handler method.
       */
      public function eventRequestToJoinAccepted(EventEnrollmentInterface $enrollment): void {
        $this->called = TRUE;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_event_enrollment_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($enrollment);

    $this->assertTrue($eda_handler->called);
  }

  /**
   * Tests process() completes when owner is a non-UserInterface account.
   *
   * @covers ::process
   * @covers ::getActorFromEntity
   */
  public function testProcessLeavesCurrentUserUnchangedWhenOwnerIsNotUser(): void {
    $owner = $this->createMock(UserInterface::class);
    $owner->method('isAnonymous')->willReturn(TRUE);

    $enrollment = $this->createMock(EventEnrollmentInterface::class);
    $enrollment->method('getOwner')->willReturn($owner);
    $this->accountSwitcher->expects($this->never())->method('switchTo');

    $eda_handler = new class() {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * Test handler method.
       */
      public function eventRequestToJoinAccepted(EventEnrollmentInterface $enrollment): void {
        $this->called = TRUE;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_event_enrollment_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($enrollment);

    $this->assertTrue($eda_handler->called);
  }

}
