<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Entity\EntityInterface;

/**
 * Type class for Entity data.
 */
class Entity {

  /**
   * Constructs the Entity type.
   *
   * @param string $id
   *   The UUID.
   * @param string $label
   *   The label.
   * @param \Drupal\social_eda\Types\Href $href
   *   The entity href.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $label,
    public readonly Href $href,
  ) {}

  /**
   * Get formatted Entity output.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return self
   *   The Entity data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromEntity(EntityInterface $entity): self {
    return new self(
      id: (string) $entity->uuid(),
      label: (string) $entity->label(),
      href: Href::fromEntity($entity),
    );
  }

}
