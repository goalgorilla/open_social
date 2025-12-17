<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_request\Unit\Plugin\BackfillHandler;

use Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestDeclineBackfillHandler;

/**
 * Unit tests for GroupMembershipRequestDeclineBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestDeclineBackfillHandler
 * @group social_group_request
 */
final class GroupMembershipRequestDeclineBackfillHandlerTest extends GroupMembershipRequestBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipRequestDeclineBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_request_decline',
      'label' => 'Group Membership Request Decline',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipRequestDecline',
    ];

    return new GroupMembershipRequestDeclineBackfillHandler(
      [],
      'group_membership_request_decline',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedStatusValue(): string {
    return 'rejected';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipRequestDecline';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_request_decline';
  }

}
