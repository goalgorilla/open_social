<?php

namespace Drupal\social_group_invite\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_group\Plugin\Action\GroupContentEntityDeleteAction;

/**
 * Remove invites for group members.
 *
 * This action allows to remove membership invitations for users.
 */
#[Action(
  id: 'social_group_invite_remove_action',
  label: new TranslatableMarkup('Remove invites'),
  confirm_form_route_name: 'views_bulk_operations.confirm',
  type: 'group_content',
)]
class SocialGroupInviteRemove extends GroupContentEntityDeleteAction {
}
