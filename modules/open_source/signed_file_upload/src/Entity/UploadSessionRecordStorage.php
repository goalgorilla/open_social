<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\Enum\UploadState;

/**
 * Custom storage for the Upload Session Record entity.
 */
class UploadSessionRecordStorage extends SqlContentEntityStorage {

  /**
   * Load a record by the upload token received from a client.
   *
   * @param string $token
   *   The upload token.
   *
   * @return \Drupal\signed_file_upload\Entity\UploadSessionRecordInterface|null
   *   A loaded record or NULL if it matched no session.
   */
  public function loadByUploadToken(string $token) : ?UploadSessionRecordInterface {
    $session = $this->loadByProperties(['upload_token_hash' => UploadSessionRecord::hashToken($token)]);
    $session = $session !== [] ? reset($session) : NULL;
    assert($session === NULL || $session instanceof UploadSessionRecordInterface);

    return $session;
  }

  /**
   * Load a record by the finalization token received from a client.
   *
   * @param string $token
   *   The finalization token.
   *
   * @return \Drupal\signed_file_upload\Entity\UploadSessionRecordInterface|null
   *   A loaded record or NULL if it matched no session.
   */
  public function loadByFinalizationToken(string $token) : ?UploadSessionRecordInterface {
    $session = $this->loadByProperties(['finalization_token_hash' => UploadSessionRecord::hashToken($token)]);
    $session = $session !== [] ? reset($session) : NULL;
    assert($session === NULL || $session instanceof UploadSessionRecordInterface);

    return $session;
  }

  /**
   * Get the record for the current session.
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The session to load the record for.
   *
   * @return \Drupal\signed_file_upload\Entity\UploadSessionRecordInterface
   *   The loaded record.
   *
   * @throws \RuntimeException
   *   If the session cannot be loaded.
   */
  public function loadForSession(UploadSession $session) : UploadSessionRecordInterface {
    $record = $this->load($session->sessionId);
    // This should never happen because in our code the session should've just
    // been loaded from the record.
    if (!$record instanceof UploadSessionRecordInterface) {
      throw new \RuntimeException("Could not load session record for uploading.");
    }

    return $record;
  }

  /**
   * Load all records that are past their expiry date but still in upload state.
   *
   * @param int $timestamp
   *   The timestamp to compare against the upload_expires field.
   * @param int $batchSize
   *   The number of entities to return in a batch.
   *
   * @return array<int,\Drupal\signed_file_upload\Entity\UploadSessionRecordInterface>
   *   The upload session records that have expired and should be cleaned up,
   *   keyed by entity ID.
   */
  public function loadUploadsRequireCleaning(int $timestamp, int $batchSize = 50) : array {
    // Var needed until Drupal entities support PHPStan generics.
    /** @var array<int,\Drupal\signed_file_upload\Entity\UploadSessionRecordInterface> $result */
    $result = $this->loadMultiple(
      $this->getQuery()
        ->condition('upload_expires', $timestamp, '<=')
        ->condition('state', UploadState::Uploading->value)
        ->range(0, $batchSize)
        ->accessCheck(FALSE)
        ->execute()
    );
    return $result;
  }

}
