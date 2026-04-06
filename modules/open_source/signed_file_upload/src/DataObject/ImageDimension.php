<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * Represents an image dimension in width and height.
 */
readonly class ImageDimension {

  /**
   * Create a new image dimension.
   *
   * @param positive-int $width
   *   The width.
   * @param positive-int $height
   *   The height.
   */
  public function __construct(
    public int $width,
    public int $height,
  ) {}

}
