<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_invite\Unit\Plugin\BackfillHandler;

use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteDeclineBackfillHandler;

/**
 * Unit tests for GroupMembershipInviteDeclineBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteDeclineBackfillHandler
 * @group social_group_invite
 */
final class GroupMembershipInviteDeclineBackfillHandlerTest extends GroupMembershipInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipInviteDeclineBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_invite_decline',
      'label' => 'Group Membership Invite Decline',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipInviteDecline',
    ];

    return new GroupMembershipInviteDeclineBackfillHandler(
      [],
      'group_membership_invite_decline',
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
    return GroupInvitation::INVITATION_REJECTED;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipInviteDecline';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_invite_decline';
  }

}
