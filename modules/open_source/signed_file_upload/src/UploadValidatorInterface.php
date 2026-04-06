<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSession;

/**
 * The upload validator for the signed file upload module.
 *
 * The signed file upload module uses its own validator rather than using
 * Drupal's built-in file validations.
 *
 * The tus uploader can support file uploads that may be larger than are
 * possible through Drupal forms since it can split a single upload in multiple
 * requests and thus work around the default PHP upload limits.
 *
 * Additionally, the tus uploader validates before upload starts based on the
 * signaled intent from the client, whereas Drupal's validators require a
 * finished file.
 */
interface UploadValidatorInterface {

  /**
   * Validate intent to upload.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   The upload constraints for the session.
   * @param \Drupal\signed_file_upload\DataObject\UploadMetadata $metadata
   *   The metadata for the upload session.
   * @param int|null $uploadLength
   *   The upload length indicated by the client if any.
   *
   * @throws \Drupal\signed_file_upload\Exception\InvalidContentException
   *   In case the intent violates any of the constraints.
   *
   * @see \Drupal\signed_file_upload\UploadSessionManager::beginUploadSession()
   */
  public function assertUploadIntent(ResolvedUploadConstraints $constraints, UploadMetadata $metadata, ?int $uploadLength) : void;

  /**
   * Check whether the file signature of the artifact is valid.
   *
   * Can be used while the file is being uploaded to continuously check the
   * signature of the file (e.g. the first few bytes of an image).
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The upload session for which to check this.
   * @param int $fileSize
   *   The current fileSize to help determine whether header validation can be
   *   performed without reopening the file.
   *
   * @return bool
   *   Whether the signature is valid.
   */
  public function validateArtifactContentSignature(UploadSession $session, int $fileSize) : bool;

  /**
   * Validate an uploaded file before it is moved to its final location.
   *
   * When this is called, the file should be fully uploaded present at its
   * artifact uri.
   *
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   The constraints of the upload.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The upload session.
   *
   * @throws \Drupal\signed_file_upload\Exception\InvalidContentException
   *    In case the uploaded file violates any of the constraints.
   */
  public function assertStagedFile(ResolvedUploadConstraints $constraints, UploadSession $session) : void;

}
