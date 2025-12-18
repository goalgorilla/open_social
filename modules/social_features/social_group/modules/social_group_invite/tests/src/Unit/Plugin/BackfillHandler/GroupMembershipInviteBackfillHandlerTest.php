<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_invite\Unit\Plugin\BackfillHandler;

use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteBackfillHandler;

/**
 * Unit tests for GroupMembershipInviteBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteBackfillHandler
 * @group social_group_invite
 */
final class GroupMembershipInviteBackfillHandlerTest extends GroupMembershipInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipInviteBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_invite',
      'label' => 'Group Membership Invite',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipInviteCreate',
    ];

    return new GroupMembershipInviteBackfillHandler(
      [],
      'group_membership_invite',
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
    return GroupInvitation::INVITATION_PENDING;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipInviteCreate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_invite';
  }

}
