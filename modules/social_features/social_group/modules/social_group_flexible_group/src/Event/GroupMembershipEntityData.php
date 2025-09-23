<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Event;

use Drupal\social_eda\Types\Entity;

/**
 * Contains data about a group membership.
 */
final class GroupMembershipEntityData {

  /**
   * Constructs the GroupMembershipEntityData type.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly array $roles,
    public readonly Entity $group,
    public readonly array $user,
  ) {}

}
