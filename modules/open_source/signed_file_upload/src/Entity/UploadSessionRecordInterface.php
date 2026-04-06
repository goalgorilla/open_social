<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadSession;

/**
 * Domain snapshot for a signed tus-aligned upload session.
 *
 * Holds progress, defer-length state, expiry, staged storage reference, and
 * constraint/destination snapshots for enforcement and auditing.
 */
interface UploadSessionRecordInterface extends EntityInterface {

  /**
   * Get the upload session from a record.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSession
   *   The valid upload session.
   */
  public function getUploadSession() : UploadSession;

  /**
   * Get the original destination information.
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination
   *   The upload destination.
   */
  public function getDestination(): EntityFieldUploadDestination|EditorUploadDestination;

  /**
   * Set the current upload offset for the session.
   *
   * @param int $offset
   *   The new offset.
   *
   * @return self
   *   The record.
   */
  public function setUploadOffset(int $offset) : self;

  /**
   * Set the upload length for the session.
   *
   * Should be used in case the length deferred once the length is known.
   *
   * @param int $length
   *   The size of the upload.
   *
   * @return self
   *   The record.
   */
  public function setUploadLength(int $length) : self;

  /**
   * Mark the record as terminated.
   *
   * This is the result of calling DELETE on the tus endpoint.
   *
   * @return self
   *   The record.
   */
  public function markTerminated() : self;

  /**
   * Mark the record as expired.
   *
   * This indicates that the upload has been cleaned up.
   *
   * @return self
   *   The record.
   */
  public function markExpired() : self;

  /**
   * Mark the upload as finalized.
   *
   * This indicates the file has been validated and moved to its final location
   * which means the partial upload artifact is gone.
   *
   * @return self
   *   The record.
   */
  public function markFinalized() : self;

}
