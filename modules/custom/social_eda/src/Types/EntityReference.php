<?php

namespace Drupal\social_eda\Types;

use Drupal\Core\Entity\EntityInterface;

/**
 * Entity reference type for CloudEvents.
 */
final class EntityReference {

  /**
   * Constructs the EntityReference type.
   *
   * @param string $id
   *   The UUID of the entity.
   * @param string $type
   *   The entity type.
   * @param \Drupal\social_eda\Types\Href $href
   *   The entity href.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $type,
    public readonly Href $href,
  ) {}

  /**
   * Get formatted EntityReference output from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return self
   *   The EntityReference data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromEntity(EntityInterface $entity): self {
    return new self(
      id: (string) $entity->uuid(),
      type: self::getEntityType($entity),
      href: Href::fromEntity($entity),
    );
  }

  /**
   * Get the appropriate type identifier for an entity.
   *
   * For specific entity types (node, group), returns the bundle name.
   * For other entities (like posts), returns the entity type ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return string
   *   The type identifier.
   */
  private static function getEntityType(EntityInterface $entity): string {
    $entity_type_id = $entity->getEntityTypeId();

    // For specific entity types, use the bundle name.
    if (in_array($entity_type_id, ['node', 'group'])) {
      $bundle = $entity->bundle();

      // If bundle is null or empty, fall back to entity type ID.
      if (empty($bundle)) {
        return $entity_type_id;
      }

      // Flexible_group bundle should be mapped to 'group'.
      if ($bundle === 'flexible_group') {
        return 'group';
      }

      return $bundle;
    }

    // For other entities (like posts), use the entity type ID.
    return $entity_type_id;
  }

}
