<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * An upload destination.
 */
interface UploadDestinationInterface {

  /**
   * Check whether this destination equals another.
   *
   * @param mixed $other
   *   The other object to check.
   *
   * @return bool
   *   Whether the data objects are equal.
   */
  public function equals(mixed $other) : bool;

}
