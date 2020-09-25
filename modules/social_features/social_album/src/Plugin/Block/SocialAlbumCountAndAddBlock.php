<?php

namespace Drupal\social_album\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\views\Views;

/**
 * Provides a block to display images count and a button for adding new images.
 *
 * @Block(
 *   id = "social_album_count_and_add_block",
 *   admin_label = @Translation("Album"),
 * )
 */
class SocialAlbumCountAndAddBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view = Views::getView('albums');
    $view->execute('embed_album');

    return [
      'count' => [
        '#markup' => $this->formatPlural(
          $view->total_rows,
          '@count image',
          '@count images'
        ),
      ],
      'link' => Link::createFromRoute(
        $this->t('Add images'),
        'entity.post.add_form',
        ['post_type' => 'photo'],
        [
          'attributes' => [
            'class' => ['btn', 'btn-primary'],
          ],
        ]
      )->toRenderable(),
    ];
  }

}
