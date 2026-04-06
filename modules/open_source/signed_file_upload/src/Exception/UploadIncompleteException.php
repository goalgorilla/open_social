<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Exception;

/**
 * Thrown when an upload is being finalized but is missing content.
 */
class UploadIncompleteException extends \Exception {

  public function __construct(int $expected, int $actual) {
    parent::__construct(sprintf(
      "Upload is incomplete, expected %d bytes but received only %d.",
      $expected,
      $actual,
    ));
  }

}
