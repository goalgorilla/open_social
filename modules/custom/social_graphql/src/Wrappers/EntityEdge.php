<?php

namespace Drupal\social_graphql\Wrappers;

use Drupal\Core\Entity\EntityInterface;

/**
 * Default implementation for edges of entities.
 */
class EntityEdge implements EdgeInterface {

  /**
   * The entity for this edge.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * EntityEdge constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this edge references to.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Return the cursor for the node associated with this edge.
   */
  public function getCursor() : string {
    // By default the cursor is based on the UUID. It's base64 encoded to avoid
    // implementor's temptation of manually choosing these values. This also
    // allows changing the used data without changing what the client sees
    // (a string).
    $uuid = $this->entity->uuid();
    if (is_null($uuid)) {
      throw new \RuntimeException("Cursors are not supported for entities without UUID");
    }
    return base64_encode($uuid);
  }

  /**
   * Return the node for associated with this edge.
   */
  public function getNode() : EntityInterface {
    return $this->entity;
  }

  /**
   * Find an entity based on a cursor.
   *
   * @param string $cursor
   *   The cursor to load an entity by.
   * @param string $entity_type
   *   The type of entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An instance of an edge.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   If an entity type does not support uuids.
   */
  public static function nodeFromCursor(string $cursor, string $entity_type) : ?EntityInterface {
    // TODO: Move cursor encoding and decoding into plugins/services.
    return \Drupal::service('entity.repository')
      ->loadEntityByUuid(
        $entity_type,
        base64_decode($cursor)
      );
  }

}
