<?php

namespace Drupal\social_group\Event;

use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;

/**
 * Contains data about the creation of an Open Social group membership.
 */
class GroupMembershipEntityData {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly array $roles,
    public readonly Entity $group,
    public readonly User $user,
    public readonly Href $href,
  ) {}

}
