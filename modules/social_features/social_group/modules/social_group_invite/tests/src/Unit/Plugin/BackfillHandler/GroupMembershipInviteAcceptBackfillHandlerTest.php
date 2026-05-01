<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_invite\Unit\Plugin\BackfillHandler;

use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteAcceptBackfillHandler;

/**
 * Unit tests for GroupMembershipInviteAcceptBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_invite\Plugin\BackfillHandler\GroupMembershipInviteAcceptBackfillHandler
 * @group social_group_invite
 */
final class GroupMembershipInviteAcceptBackfillHandlerTest extends GroupMembershipInviteBackfillHandlerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function createPlugin(): GroupMembershipInviteAcceptBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership_invite_accept',
      'label' => 'Group Membership Invite Accept',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipInviteAccept',
    ];

    return new GroupMembershipInviteAcceptBackfillHandler(
      [],
      'group_membership_invite_accept',
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
    return GroupInvitation::INVITATION_ACCEPTED;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedHandlerMethodName(): string {
    return 'groupMembershipInviteAccept';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedPluginId(): string {
    return 'group_membership_invite_accept';
  }

}
