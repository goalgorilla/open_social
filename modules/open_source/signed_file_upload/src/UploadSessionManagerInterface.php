<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload;

use Drupal\Core\Session\AccountInterface;
use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\signed_file_upload\DataObject\UploadMetadata;
use Drupal\signed_file_upload\DataObject\UploadSession;
use Drupal\signed_file_upload\DataObject\UploadSessionGrant;

/**
 * Manages signed upload sessions (tus Creation analogue + token validation).
 *
 * **Creation** is invoked from GraphQL or other OAuth-protected code: it
 * persists the session, allocates staged storage, and returns an
 * UploadSessionGrant. There is no HTTP Creation POST on the module; behavior
 * matches a successful tus Creation response as PHP values.
 */
interface UploadSessionManagerInterface {

  /**
   * Creates an upload session and returns credentials for the client.
   *
   * Resolves constraints from the destination, persists session metadata
   * without storing raw tokens, allocates staged storage, and returns the
   * upload token, tokens, tus metadata, and expiry times.
   *
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination $destination
   *   Where the file is intended to be used after finalization.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user initiating the upload; recorded on the session for auditing.
   * @param \Drupal\signed_file_upload\DataObject\UploadMetadata $metadata
   *   The metadata for the upload, corresponding to the Upload-Metadata tus
   *   header.
   * @param non-negative-int|null $uploadLength
   *   Total upload size in bytes when known at creation; must be NULL when
   *   length is deferred. Must be non-negative when provided.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSessionGrant
   *   Session id, upload token, finalization token, optional length, defer
   *   flag, tus version/extensions, expiry times, and destination.
   *
   * @throws \InvalidArgumentException
   *   When the upload type is not implemented, the destination cannot be
   *   resolved, or constraints cannot be determined.
   * @throws \Drupal\signed_file_upload\Exception\InvalidContentException
   *   If we can already tell that any of the constraints will be unmet.
   *   For example, trying to upload a PDF file with a PNG extension.
   */
  public function beginUploadSession(
    EntityFieldUploadDestination|EditorUploadDestination $destination,
    AccountInterface $account,
    UploadMetadata $metadata,
    ?int $uploadLength = NULL,
  ): UploadSessionGrant;

  /**
   * Load the session information for an upload token.
   *
   * @param non-empty-string $token
   *   An upload token provided by the tus client.
   *
   * @return \Drupal\signed_file_upload\DataObject\UploadSession|null
   *   The upload session information or NULL if the token does not correspond
   *   to a session.
   */
  public function loadSession(string $token) : ?UploadSession;

}
