<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Type class for Entity data.
 */
class User {

  /**
   * Constructs the User type.
   *
   * @param string $id
   *   The UUID.
   * @param string $displayName
   *   The display name.
   * @param \Drupal\social_eda\Types\Href $href
   *   The entity href.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $displayName,
    public readonly Href $href,
  ) {}

  /**
   * Get formatted User output.
   *
   * @param \Drupal\Core\Entity\EntityInterface|\Drupal\user\UserInterface $entity
   *   The User object.
   *
   * @return self
   *   The User data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromEntity(EntityInterface|UserInterface $entity): self {
    return new self(
      id: (string) $entity->uuid(),
      displayName: (string) ($entity instanceof UserInterface ? $entity->getDisplayName() : $entity->label()),
      href: Href::fromEntity($entity),
    );
  }

}
