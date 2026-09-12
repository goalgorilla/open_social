<?php

declare(strict_types=1);

namespace Drupal\social_group_request\TestControlInterface\Dto;

/**
 * Single membership-request row for the `group membership requests:` step.
 *
 * Only `group`, `user` and `status` take effect. `field_grequest_message`
 * and `field_access_level` are accepted and ignored: tests pass them today
 * but they have never been applied, and honouring them now would silently
 * change what those tests assert. Use `group requests:` to set fields on
 * the request itself.
 */
final readonly class GroupMembershipRequestRow {

  public function __construct(
    public string $group,
    public string $user,
    public ?string $status = NULL,
    public ?string $field_grequest_message = NULL,
    public ?string $field_access_level = NULL,
  ) {}

}
