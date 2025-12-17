<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_request\Unit\Plugin\BackfillHandler;

use Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestBackfillHandler;

/**
 * Unit tests for GroupMembershipRequestBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_request\Plugin\BackfillHandler\GroupMembershipRequestBackfillHandler
 * @group social_group_request
 */
final class GroupMembershipRequestBackfillHandlerTest extends GroupMembershipRequestBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipRequestBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_request',
      'label' => 'Group Membership Request',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipRequestCreate',
    ];

    return new GroupMembershipRequestBackfillHandler(
      [],
      'group_membership_request',
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
    return 'pending';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipRequestCreate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_request';
  }

}
