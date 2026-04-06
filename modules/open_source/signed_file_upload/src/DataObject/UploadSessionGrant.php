<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * Credentials returned when an upload session is created.
 *
 * This is an analogue to the tus Creation extension.
 *
 * Creation is performed via GraphQL (or similar) calling
 * UploadSessionManagerInterface::beginUploadSession(), not via an HTTP
 * Creation POST. Fields mirror what a tus client needs for Core, Expiration,
 * Termination, and creation-defer-length capability discovery.
 *
 * **Client-facing tus URL:** Integrators should build the resource URL with
 * Drupal’s route builder, e.g.
 * `Url::fromRoute('signed_file_upload.tus', ['token' => $this->uploadToken])`
 * (and `setAbsolute()` / `toString()` when an absolute URL is required). The
 * path includes the opaque `uploadToken`, which authorizes byte operations on
 * the tus endpoint without OAuth.
 */
readonly class UploadSessionGrant {

  /**
   * Create a new Upload Session Grant.
   *
   * @param non-empty-string $uploadToken
   *   Opaque secret embedded in the tus resource URL (`/api/tus/{token}`) and
   *   used with validateUploadToken() for HEAD/PATCH/DELETE.
   * @param non-empty-string $finalizationToken
   *   Secret for finalizeUpload() (OAuth-backed callers, not the tus byte URL).
   * @param \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination $destination
   *   Intended attachment target after finalization.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The upload session state at time of creation.
   */
  public function __construct(
    public string $uploadToken,
    public string $finalizationToken,
    public EditorUploadDestination|EntityFieldUploadDestination $destination,
    public UploadSession $session,
  ) {}

}
