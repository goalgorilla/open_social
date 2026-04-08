<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers\SignedFileUpload;

use Drupal\signed_file_upload\DataObject\UploadSessionGrant;

/**
 * Value object for the GraphQL StagedUploadGrant type.
 *
 * Holds both the client-selected upload target (enum string from the request)
 * and the tus credentials from signed_file_upload. GraphQL field resolvers use
 * this wrapper so `target` is stable even when several enum values share one
 * server-side destination.
 */
readonly class StagedUploadGrant {

  /**
   * Constructs a staged upload grant for the API response.
   *
   * @param string $target
   *   The `StagedUploadTarget` enum value name from the client's request.
   * @param \Drupal\signed_file_upload\DataObject\UploadSessionGrant $sessionGrant
   *   Upload token, finalization token, destination, and session state from
   *   the upload manager.
   */
  public function __construct(
    public string $target,
    public UploadSessionGrant $sessionGrant,
  ) {}

}
