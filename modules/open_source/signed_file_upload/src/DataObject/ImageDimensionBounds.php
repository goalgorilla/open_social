<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * Represents the optional minimum and maximum resolution of an image.
 */
readonly class ImageDimensionBounds {

  /**
   * Create a new dimension bounds instance.
   *
   * At least one of the two arguments must be non-null.
   *
   * @param \Drupal\signed_file_upload\DataObject\ImageDimension|null $max
   *   The maximum image dimensions.
   * @param \Drupal\signed_file_upload\DataObject\ImageDimension|null $min
   *   The minimum image dimensions.
   */
  public function __construct(
    public ?ImageDimension $max,
    public ?ImageDimension $min,
  ) {
    assert($this->max !== NULL || $this->min !== NULL, "At least one of the dimension bounds should be non-null.");
  }

}
