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
   * The limit of images that displayed in the post.
   */
  const LIMIT = 11;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (!$items->isEmpty()) {
      foreach (array_reverse($items->referencedEntities(), TRUE) as $key => $file) {
        if (self::LIMIT < $key) {
          if ($items->get($key)) {
            $items->removeItem($key);
          }
        }
      }
    }

    return parent::viewElements($items, $langcode);
  }

}
