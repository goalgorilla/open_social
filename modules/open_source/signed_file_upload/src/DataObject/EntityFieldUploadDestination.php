<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * A file that will be attached to a field of a Drupal entity.
 */
readonly class EntityFieldUploadDestination implements UploadDestinationInterface {

  /**
   * Create a new entity field upload destination.
   *
   * @param non-empty-string $entityTypeId
   *   The entity type ID of the destination (e.g. 'node' or 'user').
   * @param non-empty-string $bundle
   *   The bundle of the destination, this is the entity type ID if the entity
   *   does not support bundles (e.g. 'topic' or 'user').
   * @param non-empty-string $fieldName
   *   The name of the field to which the file will be attached.
   */
  public function __construct(
    public string $entityTypeId,
    public string $bundle,
    public string $fieldName,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function equals(mixed $other) : bool {
    if (!$other instanceof self) {
      return FALSE;
    }

    return $this->entityTypeId === $other->entityTypeId
      && $this->bundle === $other->bundle
      && $this->fieldName === $other->fieldName;
  }

}
