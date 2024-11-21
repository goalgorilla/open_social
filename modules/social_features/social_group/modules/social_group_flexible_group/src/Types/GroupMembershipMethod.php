<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Types;

use Drupal\group\Entity\GroupInterface;

/**
 * Type class for Group membership data.
 */
class GroupMembershipMethod {

  /**
   * Constructs the GroupMembershipMethod type.
   *
   * @param string $method
   *   The method.
   */
  public function __construct(
    public readonly string $method,
  ) {}

  /**
   * Get formatted GroupMembership output.
   *
   * @param \Drupal\group\Entity\GroupInterface $entity
   *   The Group object.
   *
   * @return self
   *   The GroupMembership data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromEntity(GroupInterface $entity): self {
    return new self(
      method: (string) $entity->get('field_group_allowed_join_method')->value,
    );
  }

}
