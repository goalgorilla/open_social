<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Dto;

/**
 * Single membership row for the group membership operations.
 *
 * Only `group` and `user` take effect. `group_roles` and `uid` are accepted
 * and ignored: tests pass them today but they have never been applied, and
 * honouring them now would silently change what those tests assert. Use
 * `I add :role_id role to :group_member in :group_name group` to give a
 * member a role.
 */
final readonly class GroupMemberRow {

  public function __construct(
    public string $group,
    public string $user,
    public ?string $group_roles = NULL,
    public ?string $uid = NULL,
  ) {}

}
