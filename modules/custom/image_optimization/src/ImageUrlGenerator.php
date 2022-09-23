<?php

namespace Drupal\image_optimization;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * Provides the ImageUrlGenerator.
 */
class ImageUrlGenerator implements ImageUrlGeneratorInterface {

  use AsymmetricCryptTrait;

  /**
   * Transform fit types.
   *
   * @var string[]
   */
  protected array $fitTypes = [
    'clip',
  ];

  /**
   * Ratios used for bucketing.
   *
   * @var int[]|float[]
   */
  protected array $ratios = [
    2,
    4 / 3,
    5 / 4,
    16 / 9,
    1,
  ];

  /**
   * Image sizes used for bucketing.
   *
   * @var int[]
   */
  protected array $sizes = [
    24,
    44,
    100,
    300,
    500,
    1000,
    1500,
    2500,
  ];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The public key for encryption.
   *
   * @var string
   */
  protected string $publicKey;

  /**
   * ImageUrlGenerator constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *
   * @throws \Exception
   *   When the public key is not available.
   */
  public function __construct(FileSystemInterface $file_system, ConfigFactoryInterface $config_factory) {
    $this->fileSystem = $file_system;
    $public_key_path = $config_factory->get('image_optimization.settings')->get('public_key');
    $this->publicKey = $this->getPublicKey($public_key_path);
  }

  /**
   * {@inheritdoc}
   */
  public function generate(
    string $uuid,
    ImageInterface $image,
    ?int $width = NULL,
    ?int $height = NULL,
    ?string $extension = NULL,
    string $fit = 'clip'
  ): string {
    if (!in_array($fit, $this->fitTypes)) {
      throw new \Exception('Provided fit type not supported.');
    }

    $query = [
      'uuid' => $uuid,
      'fit' => $fit,
    ];
    $original_extension = pathinfo($image->getSource(), PATHINFO_EXTENSION);
    $extension_suffix = $extension ?? $original_extension;

    if ($extension !== NULL && $extension !== $original_extension) {
      $query['extension'] = $extension;
    }

    $query = http_build_query(array_merge($query, $this->getDimensionsQuery($image, $width, $height)));

    return "/_i/{$this->encrypt($query, $this->publicKey)}.{$extension_suffix}";
  }

  /**
   * Get the dimensions query.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image object.
   * @param int|null $width
   *   The width in px.
   * @param int|null $height
   *   The height in px.
   *
   * @return array
   *   Returns array query.
   */
  protected function getDimensionsQuery(ImageInterface $image, ?int $width = NULL, ?int $height = NULL): array {
    $query = [];

    // If there are no dimensions provided, this mean there is no
    // transformation required.
    if ($width === NULL && $height === NULL) {
      return $query;
    }

    // If there is only one dimensions, we need to fallback on the original
    // image ratio.
    if ($width === NULL || $height === NULL) {
      $original_width = $image->getWidth();
      $original_height = $image->getHeight();

      // If there is no original dimensions available, we don't transform.
      if ($original_width === NULL || $original_height === NULL) {
        return $query;
      }

      // Get closest ratio based on the original image dimensions.
      $ratio = $this->findClosestRatio($original_width, $original_height);
      $is_landscape = $this->isLandscape($original_width, $original_height);

      // Case when no height is provided.
      if ($width !== NULL) {
        $query['width'] = $this->findClosestSize($width);
        $calculated_height = $is_landscape ? $query['width'] / $ratio : $query['width'] * $ratio;
        $query['height'] = round($calculated_height);
      }
      // Case when no width is provided.
      elseif ($height !== NULL) {
        $closest_height = $this->findClosestSize($height);
        $calculated_width = $is_landscape ? $closest_height * $ratio : $closest_height / $ratio;
        $query['width'] = round($calculated_width);
        $query['height'] = $closest_height;
      }

      return $query;
    }

    // Both dimensions are available.
    $ratio = $this->findClosestRatio($width, $height);
    if ($this->isLandscape($width, $height)) {
      $query['width'] = $this->findClosestSize($width);
      $query['height'] = round($query['width'] / $ratio);
    }
    else {
      $closest_height = $this->findClosestSize($height);
      $query['width'] = round($closest_height / $ratio);
      $query['height'] = $closest_height;
    }

    return $query;
  }

  /**
   * Find the closes ratio based on width and height.
   *
   * @param int $width
   *   The width in px.
   * @param int $height
   *   The height in px.
   *
   * @return float
   *   Returns the closest ratio.
   */
  protected function findClosestRatio(int $width, int $height): float {
    // Check if the dimensions match.
    if ($width === $height) {
      return 1;
    }

    $is_landscape = $this->isLandscape($width, $height);
    $calculate_ratio = $width / $height;
    $check_ratio = $is_landscape ? $calculate_ratio : 1 / $calculate_ratio;

    $distance = PHP_INT_MAX;
    $selected = NULL;

    // Find the ratio that is closest to one of our allowed ratios.
    foreach ($this->ratios as $ratio) {
      $check_distance = abs($check_ratio - $ratio);
      if ($check_distance < $distance) {
        $distance = $check_distance;
        $selected = $ratio;
      }
    }

    return (float) $selected;
  }

  /**
   * Find the closest size based on a dimension.
   *
   * @param int $original_size
   *   The size in px.
   *
   * @return int
   *   Returns the closest size.
   */
  protected function findClosestSize(int $original_size): int {
    // Check if the size matches.
    if (in_array($original_size, $this->sizes)) {
      return $original_size;
    }

    $size_diff = [];

    // Iterate through the sizes and get the difference.
    foreach ($this->sizes as $size) {
      $size_diff[$size] = abs($size - $original_size);
    }

    // Sort the differences ascending.
    asort($size_diff);

    // The first one is the closest one.
    return (int) key($size_diff);
  }

  /**
   * Checks if the image is landscape based on width and height.
   *
   * @param int $width
   *   The width in px.
   * @param int $height
   *   The height in px.
   *
   * @return bool
   *   Return true if is landscape, otherwise false.
   */
  protected function isLandscape(int $width, int $height): bool {
    return $width > $height;
  }

  /**
   * Get the public key.
   *
   * @param string $public_key_path
   *   The public key path.
   *
   * @return string
   *   Returns the public key.
   *
   * @throws \Exception
   */
  protected function getPublicKey(string $public_key_path): string {
    $file_path = $this->fileSystem->realpath($public_key_path) ?: $public_key_path;
    $key = file_get_contents($file_path);

    if (!$key) {
      throw new \Exception('The public key is not available.');
    }

    return $key;
  }

}
