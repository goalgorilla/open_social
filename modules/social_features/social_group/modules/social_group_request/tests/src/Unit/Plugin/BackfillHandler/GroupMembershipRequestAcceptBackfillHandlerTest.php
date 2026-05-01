<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_request\Unit\Plugin\BackfillHandler;

use Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestAcceptBackfillHandler;

/**
 * Unit tests for GroupMembershipRequestAcceptBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestAcceptBackfillHandler
 * @group social_group_request
 */
final class GroupMembershipRequestAcceptBackfillHandlerTest extends GroupMembershipRequestBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipRequestAcceptBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_request_accept',
      'label' => 'Group Membership Request Accept',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipRequestAccept',
    ];

    return new GroupMembershipRequestAcceptBackfillHandler(
      [],
      'group_membership_request_accept',
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
  protected function getExpectedStatusValue(): string {
    return 'approved';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipRequestAccept';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_request_accept';
  }

}
