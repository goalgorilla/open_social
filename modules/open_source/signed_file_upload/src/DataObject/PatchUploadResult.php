<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

use Drupal\signed_file_upload\Enum\PatchResult;

/**
 * Result of a successful tus PATCH (Core + Expiration).
 */
readonly class PatchUploadResult {

  /**
   * Create a new Patch Upload Result.
   *
   * @param \Drupal\signed_file_upload\Enum\PatchResult $result
   *   The result of the operation.
   * @param \Drupal\signed_file_upload\DataObject\UploadSession $session
   *   The updated session information.
   */
  public function __construct(
    public PatchResult $result,
    public UploadSession $session,
  ) {}

}
