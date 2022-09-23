<?php

namespace Drupal\image_optimization;

use Drupal\Core\Image\ImageInterface;

/**
 * The image derivative generator interface.
 */
interface ImageDerivativeGeneratorInterface {

  /**
   * The default image transform fit.
   */
  const DEFAULT_IMAGE_TRANSFORM_FIT = 'clip';

  /**
   * Transforms image by a specific transform fit plugin.
   *
   * @param int|null $width
   *   The target width, in pixels. If this value is null then the scaling will
   *   be based only on the height value.
   * @param int|null $height
   *   The target height, in pixels. If this value is null then the scaling will
   *   be based only on the width value.
   * @param string $fit
   *   The image transform fit plugin id.
   *
   * @return $this
   *   Returns the object.
   */
  public function transformDimensions(?int $width, ?int $height = NULL, string $fit = self::DEFAULT_IMAGE_TRANSFORM_FIT): self;

  /**
   * Transforms the image to a specific extension.
   *
   * @param string $extension
   *   The extension type to transform to.
   *
   * @return $this
   *   Returns the object.
   */
  public function transformExtension(string $extension): self;

  /**
   * Generates the transformed image.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function generate(string $uri): bool;

  /**
   * Get the destination uri of the derivative.
   *
   * @param string $uri
   *   The image uri.
   *
   * @return string
   *   Returns the destination uri.
   */
  public function getDestinationUri(string $uri): string;

  /**
   * Retrieve the image.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   The image.
   */
  public function getImage(): ImageInterface;

}
