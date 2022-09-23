<?php

namespace Drupal\image_optimization;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * The image derivative generator.
 */
class ImageDerivativeGenerator implements ImageDerivativeGeneratorInterface {

  /**
   * The active transform fit.
   *
   * @var string
   */
  protected string $imageTransformFit = self::DEFAULT_IMAGE_TRANSFORM_FIT;

  /**
   * The image file object.
   *
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected ImageInterface $image;

  /**
   * The ImageDerivativeGenerator construct.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image object.
   */
  public function __construct(ImageInterface $image) {
    $this->image = $image;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(?int $width, ?int $height = NULL, string $fit = self::DEFAULT_IMAGE_TRANSFORM_FIT): self {
    switch ($fit) {
      default:
        $this->getImage()->scale($width, $height);
        break;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function transformExtension(string $extension): self {
    $this->getImage()->convert($extension);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $uri): bool {
    $image = $this->getImage();
    if (!$image->isValid()) {
      return FALSE;
    }

    $destination = $this->getDestinationUri($uri);
    $file_system = $this->getFileSystem();

    // Get the folder for the final location of this image derivative.
    $directory = $file_system->dirname($destination);

    // Build the destination folder tree if it doesn't already exist.
    if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      \Drupal::logger('image_optimization')->error('Failed to create image transform directory: %directory', ['%directory' => $directory]);
      return FALSE;
    }

    if (!$image->save($destination)) {
      if (file_exists($destination)) {
        \Drupal::logger('image_optimization')->error('Cached image file %destination already exists. There may be an issue with your rewrite configuration.', ['%destination' => $destination]);
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationUri(string $uri): string {
    return "public://_i/{$uri}";
  }

  /**
   * {@inheritdoc}
   */
  public function getImage(): ImageInterface {
    return $this->image;
  }

  /**
   * Get the file system.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   The file system service.
   */
  protected function getFileSystem(): FileSystemInterface {
    return \Drupal::service('file_system');
  }

}
