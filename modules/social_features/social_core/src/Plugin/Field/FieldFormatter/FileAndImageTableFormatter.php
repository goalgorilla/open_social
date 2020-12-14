<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\file\FileInterface;

/**
 * Plugin implementation of the 'file_image_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_default",
 *   label = @Translation("File and Image in a table"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAndImageTableFormatter extends ImageFormatter {

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
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$delta] += ['#attributes' => []];
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
      else {
        // If it's an image we need to add the image width and height
        // so Photoswipe knows what to render.
        $image = $this->getImage($file);
        $elements[$delta]['#item']->height = $image->getHeight();
        $elements[$delta]['#item']->width = $image->getWidth();
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
    $image = $this->getImage($file);

    if ($image === NULL) {
      return FALSE;
    }

    return $image->isValid();
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
