<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Entity\EntityInterface;

/**
 * Type class for Href data.
 */
class Href {

  /**
   * Constructs the Href type.
   *
   * @param string $canonical
   *   The canonical url of the entity.
   */
  public function __construct(
    public readonly string $canonical,
  ) {}

  /**
   * Get formatted Href output.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return self
   *   The Href data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromEntity(EntityInterface $entity): self {
    return new self(
      canonical: $entity->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->toString(),
    );
  }

}
