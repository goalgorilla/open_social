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
    // Grab all elements from the parent view.
    $elements = parent::viewElements($items, $langcode);
    if (!$items->isEmpty()) {
      // If it's only one, we can safely return without updating image styles.
      if ($items->count() === 1) {
        return $elements;
      }
      // If it's more than one, lets remove it after we hit our limit,
      // and render them using a different image style to make sure they are
      // square in size and multiple can fit together in the post view.
      foreach (array_reverse($this->getEntitiesToView($items, $langcode)) as $delta => $file) {
        $elements[$delta]['#image_style'] = 'social_x_large';
        if (self::LIMIT < $delta) {
          unset($elements[$delta]);
        }
      }
    }

    // Return all updated elements with a max of self::LIMIT.
    return $elements;
  }

}
