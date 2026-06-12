<?php

declare(strict_types=1);

namespace Drupal\social_comment\Access;

/**
 * Scope and permission constants for hidden comment field access.
 *
 * Extensions that grant view access to hidden comment fields (replacing the
 * removed hook_social_comment_hidden_field_bypass) should register an
 * access_policy service tagged access_policy. The policy must apply to
 * SCOPE_HIDDEN_COMMENT_FIELD, grant PERMISSION_VIEW_HIDDEN for each node/field
 * pair via CalculatedPermissionsItem, and use identifier() for the item id.
 *
 * @see \Drupal\social_comment\Access\HiddenCommentNodeOwnerAccessPolicy
 * @see \Drupal\social_discussion_moderator\Access\DiscussionModeratorHiddenCommentAccessPolicy
 */
final class HiddenCommentFieldAccessPolicy {

  public const SCOPE_HIDDEN_COMMENT_FIELD = 'social_comment:hidden_comment_field';

  public const PERMISSION_VIEW_HIDDEN = 'view hidden comments';

  /**
   * Builds the calculated-permissions item identifier for a node/field pair.
   */
  public static function identifier(int|string $nid, string $field_name): string {
    return 'node:' . $nid . ':' . $field_name;
  }

}
