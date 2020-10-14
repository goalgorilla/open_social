<?php

namespace Drupal\social_post_album\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'album_image' formatter.
 *
 * @FieldFormatter(
 *   id = "album_image",
 *   label = @Translation("Album image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class AlbumImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $files = $items->referencedEntities();
    $files = array_reverse($files, TRUE);
    $limit = 11;

    if (!empty($files)) {
      foreach ($files as $key => $file) {
        if ($limit < $key) {
          if ($items->get($key)) {
            $items->removeItem($key);
          }
        }
      }
    }

    return parent::viewElements($items, $langcode);
  }

}
