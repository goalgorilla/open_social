<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\signed_file_upload\DataObject\PatchUploadResult;
use Drupal\signed_file_upload\DataObject\UploadDestinationInterface;
use Drupal\signed_file_upload\DataObject\UploadSession;

/**
 * Handles file bytes and promotion for a signed upload session (tus-aligned).
 *
 * Aligns with the tus resumable upload protocol: Core (HEAD, PATCH), Expiration
 * (`Upload-Expires`), Termination (DELETE), and creation-defer-length (total
 * size may be supplied on the first PATCH via `Upload-Length`).
 *
 * Session permits (grants, upload/finalization secrets, invalidation) live on
 * UploadSessionManagerInterface. This interface owns durable staged state:
 * current offset, optional deferred length, temporary file bytes, and
 * promotion to a managed file on finalization.
 *
 * **HTTP vs service:** Controllers map requests to these methods (e.g. PATCH
 * `Content-Type: application/offset+octet-stream`, `Upload-Offset`,
 * `Upload-Length`, status codes 204/409/415/412). This layer receives parsed
 * values and returns data for response headers.
 *
 * Uploads are **sequential** (single PATCH stream per offset); parallel parts
 * would require the tus Concatenation extension, which is not supported here.
 */
interface SignedUploadFileLifecycleInterface {

  /**
   * Appends bytes at the current tus offset (PATCH, Core).
   *
   * The client offset must equal the stored offset or implementations throw
   * (HTTP 409 Conflict at the controller). When the session uses defer-length
   * and the total length is not yet fixed, the client must pass the total
   * upload size in $uploadLength (tus: `Upload-Length` on that PATCH). Once
   * set, the total length must not change.
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The active upload session this upload is in.
   * @param resource $input
   *   The stream that provides the upload input.
   * @param int $uploadOffset
   *   The claimed upload offset from the client.
   * @param int|null $uploadLength
   *   The requested upload length from the client.
   *
   * @return \Drupal\signed_file_upload\DataObject\PatchUploadResult
   *   The result of the upload.
   *
   * @throws \InvalidArgumentException
   *   When the session or token is invalid, offset mismatch, length is
   *   missing when required, or constraints are violated.
   * @throws \RuntimeException
   *   When storage fails.
   * @throws \Drupal\signed_file_upload\Exception\OperationConflictException
   *    In case the file for the session is already in use and the lock cannot
   *    be acquired.
   */
  public function patchUpload(
    UploadSession $session,
    $input,
    int $uploadOffset,
    ?int $uploadLength,
  ): PatchUploadResult;

  /**
   * Terminates the upload and frees staged resources (tus Termination, DELETE).
   *
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   A valid upload session that should be terminated.
   *
   * @throws \RuntimeException
   *   When cleanup fails.
   * @throws \Drupal\signed_file_upload\Exception\OperationConflictException
   *    In case the file for the session is already in use and the lock cannot
   *    be acquired.
   */
  public function deleteUpload(
    UploadSession $session,
  ): void;

  /**
   * Validates the finalization token and promotes the staged file to permanent.
   *
   * Verifies the upload is complete per tus rules (offset equals total length
   * when known), applies constraints from the session, and returns the managed
   * file.
   *
   * @param string $finalizationToken
   *   The secret issued with the session grant for this step.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user performing finalization; used for access and ownership checks.
   * @param \Drupal\signed_file_upload\DataObject\UploadDestinationInterface $expectedDestination
   *   The expected destination for this the upload should be to ensure the
   *   finalization token is used in the correct place.
   *
   * @return \Drupal\file\FileInterface
   *   The permanent managed file after finalization.
   *
   * @throws \InvalidArgumentException
   *   When the session or token is invalid, the upload is incomplete, or
   *   inputs are inconsistent.
   * @throws \RuntimeException
   *   When the staged file fails constraint validation or promotion fails.
   * @throws \Drupal\signed_file_upload\Exception\OperationConflictException
   *    In case the file for the session is already in use and the lock cannot
   *    be acquired.
   */
  public function finalizeUpload(
    string $finalizationToken,
    AccountInterface $account,
    UploadDestinationInterface $expectedDestination,
  ): FileInterface;

}
