<?php

namespace Drupal\social_core\Service;

use Drupal\image\ImageEffectInterface;
use Drupal\image\ImageEffectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Computes the size PhotoSwipe must declare for an original image.
 */
class PhotoswipeSize {

  /**
   * The auto-orient image effect, created on first use.
   */
  protected ?ImageEffectInterface $autoOrient = NULL;

  /**
   * Constructs a PhotoswipeSize object.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.image.effect')]
    protected ?ImageEffectManager $imageEffectManager,
  ) {}

  /**
   * Returns the "width x height" a browser shows the original image at.
   *
   * PhotoSwipe links the original file, which browsers rotate per its EXIF
   * orientation, while stored and GD dimensions are the raw pixel size.
   *
   * @param int|null $width
   *   The raw pixel width.
   * @param int|null $height
   *   The raw pixel height.
   * @param string $uri
   *   The URI of the original image.
   *
   * @return string
   *   The oriented size, e.g. "900x1200"; "x" when a dimension is unknown.
   */
  public function get(?int $width, ?int $height, string $uri): string {
    $size = ['width' => $width, 'height' => $height];
    // NULL only in kernel tests that enable social_core without image.
    if ($this->imageEffectManager === NULL) {
      return $width . 'x' . $height;
    }
    if ($this->autoOrient === NULL) {
      /** @var \Drupal\image\ImageEffectInterface $auto_orient */
      $auto_orient = $this->imageEffectManager->createInstance('image_effects_auto_orient');
      $this->autoOrient = $auto_orient;
    }
    $this->autoOrient->transformDimensions($size, $uri);

    return $size['width'] . 'x' . $size['height'];
  }

}
