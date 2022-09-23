<?php

namespace Drupal\image_optimization;

use Drupal\Core\Image\ImageInterface;

/**
 * Provides the ImageUrlGeneratorInterface.
 */
interface ImageUrlGeneratorInterface {

  /**
   * Generate an image URL that can be used for deriving a transformed image.
   *
   * @param string $uuid
   *   The file entity uuid.
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image object.
   * @param int|null $width
   *   The width to transform to.
   * @param int|null $height
   *   The height to transform to.
   * @param string|null $extension
   *   The extension to transform to.
   * @param string $fit
   *   The transform fit type to use.
   *
   * @return string
   *   Returns URL that can be used to derive a transformed image.
   *
   * @throws \Exception
   */
  public function generate(
    string $uuid,
    ImageInterface $image,
    ?int $width = NULL,
    ?int $height = NULL,
    ?string $extension = NULL,
    string $fit = 'clip'
  ): string;

}
