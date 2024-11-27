<?php

namespace Drupal\social_group_flexible_group\Event;

use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_group_flexible_group\Types\GroupMembershipMethod;
use Drupal\social_group_flexible_group\Types\GroupVisibility;

/**
 * Contains data about the creation of an Open Social group.
 */
class GroupEntityData {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly string $label,
    public readonly GroupVisibility|null $visibility,
    public readonly array $contentVisibility,
    public readonly GroupMembershipMethod|null $membership,
    public readonly ?string $type,
    public readonly User $author,
    public readonly Address $address,
    public readonly Href $href,
  ) {}

}
