<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\file\FileInterface;
use Drupal\social_core\Service\PhotoswipeSize;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_image_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_default",
 *   label = @Translation("File and Image in a table"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileAndImageTableFormatter extends ImageFormatter {

  /**
   * The PhotoSwipe size calculator.
   */
  protected PhotoswipeSize $photoswipeSize;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->photoswipeSize = $container->get(PhotoswipeSize::class);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Grab elements from the ImageFormatter and see if we can attach files?
    $elements = parent::viewElements($items, $langcode);

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;

      // If it's a File we render it as a file_link.
      if (!$this->isImage($file)) {
        $elements[$delta] = [
          '#theme' => 'file_link',
          '#file' => $file,
          '#description' => $item->get('description')->getValue(),
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
      }
      else {
        // Width/height stay the raw pixel size: the thumbnail's image style
        // applies the EXIF orientation itself. Only PhotoSwipe, which shows
        // the original, gets the oriented size.
        $image = $this->getImage($file);
        $elements[$delta]['#item']->height = $image->getHeight();
        $elements[$delta]['#item']->width = $image->getWidth();
        $elements[$delta]['#item']->photoswipe_size = $this->photoswipeSize->get($image->getWidth(), $image->getHeight(), $image->getSource());
        $elements[$delta]['#item']->is_image = TRUE;
      }
    }

    return $elements;
  }

  /**
   * Check if its a file or image so we know what to render.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity. This function may resize the file affecting its size.
   *
   * @return bool
   *   TRUE when it's an image and not a file
   */
  private function isImage(FileInterface $file) {
    return $this->getImage($file)->isValid();
  }

  /**
   * Grab the Image if we are dealing with one.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity. This function may resize the file affecting its size.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An Image object.
   */
  private function getImage(FileInterface $file) {
    // Make sure we deal with a file.
    $image_factory = \Drupal::service('image.factory');

    return $image_factory->get($file->getFileUri());
  }

}
