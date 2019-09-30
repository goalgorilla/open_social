<?php

namespace Drupal\social_image_copyright\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\FileInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'Advanced Image' formatter.
 *
 * @FieldFormatter(
 *   id = "advanced_image",
 *   label = @Translation("Advanced Image"),
 *   field_types = {
 *      "advanced_image",
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class AdvancedImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    foreach ($files as $delta => $file) {
      assert($file instanceof FileInterface);
      $item = $file->_referringItem;

      if (!empty($item->copyright)) {
        $elements[$delta]['#item_attributes']['data-copyright'] = $item->copyright;
        $elements[$delta]['#item_attributes']['class'][] = 'copyright-attribute';
      }
    }

    return $elements;
  }

}
