<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

use Drupal\signed_file_upload\Enum\UploadState;

/**
 * Tus-aligned upload resource state for HEAD responses (Core + Expiration).
 *
 * When `lengthDeferred` is TRUE, HTTP adapters should send
 * `Upload-Defer-Length: 1` and omit `Upload-Length` until the total size is
 * fixed (tus creation-defer-length).
 */
readonly class UploadSession {

  /**
   * Create a new Upload Session.
   *
   * @param non-negative-int $sessionId
   *   The internal storage ID of the session.
   * @param \Drupal\signed_file_upload\Enum\UploadState $state
   *   The upload session state. Should UploadState::Uploading for any
   *   valid session.
   * @param non-negative-int $offset
   *   Current byte offset (maps to `Upload-Offset`).
   * @param non-negative-int|null $uploadLength
   *   Total upload size in bytes when known (maps to `Upload-Length`), or NULL
   *   while length is still deferred (send `Upload-Defer-Length: 1`).
   * @param \DateTimeImmutable $uploadExpiresAt
   *   When the unfinished upload expires (maps to `Upload-Expires`,
   *   Expiration).
   * @param non-empty-string $artifactUri
   *   The internal artifact URI that the file is uploaded to.
   * @param \Drupal\signed_file_upload\DataObject\ResolvedUploadConstraints $constraints
   *   The internal constraints for the upload session.
   * @param \Drupal\signed_file_upload\DataObject\UploadMetadata $metadata
   *   The server specific metadata for the file upload.
   */
  public function __construct(
    public int $sessionId,
    public UploadState $state,
    public int $offset,
    public ?int $uploadLength,
    public \DateTimeImmutable $uploadExpiresAt,
    public string $artifactUri,
    public ResolvedUploadConstraints $constraints,
    public UploadMetadata $metadata,
  ) {}

}
