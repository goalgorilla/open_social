<?php

namespace Drupal\social_group_invite\Plugin\Action;

use Drupal\social_group\Plugin\Action\GroupContentEntityDeleteAction;

/**
 * Remove invites for group members.
 *
 *  This action allows to remove membership invitations for users.
 *
 * @Action(
 *   id = "social_group_invite_remove_action",
 *   label = @Translation("Remove invites"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class SocialGroupInviteRemove extends GroupContentEntityDeleteAction {
}
