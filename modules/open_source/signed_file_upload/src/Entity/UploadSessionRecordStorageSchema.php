<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Entity;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Adds database unique keys for token hash columns used in loadByProperties().
 *
 * The UniqueField validation constraint does not define storage-level unique
 * keys; this handler ensures indexed uniqueness for upload_token_hash and
 * finalization_token_hash on the base table.
 */
final class UploadSessionRecordStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    $base_table = $this->storage->getBaseTable();
    $schema[$base_table]['unique keys'] += [
      'upload_session_upload_token_hash' => ['upload_token_hash'],
      'upload_session_finalization_token_hash' => ['finalization_token_hash'],
    ];
    return $schema;
  }

}
