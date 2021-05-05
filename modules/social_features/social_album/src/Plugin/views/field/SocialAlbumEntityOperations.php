<?php

namespace Drupal\social_album\Plugin\views\field;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Renders all operations links for a post.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_album_post_operations")
 */
class SocialAlbumEntityOperations extends EntityOperations implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    foreach (get_object_vars($values) as $key => $value) {
      if (preg_match('/_delta$/', $key)) {
        break;
      }
    }

    $entity = $this->getEntity($values);

    return [
      '#lazy_builder' => [
        [$this, 'renderLinks'],
        [
          $values->_entity->id(),
          $entity->id(),
          $entity->field_post_image->get($value)->target_id,
        ],
      ],
    ];
  }

  /**
   * Lazy_builder callback; builds a post's links.
   *
   * @param int $node_id
   *   The post entity ID.
   * @param int $post_id
   *   The post entity ID.
   * @param int $file_id
   *   The file entity ID.
   *
   * @return array
   *   A renderable array representing the post links.
   */
  public static function renderLinks($node_id, $post_id, $file_id) {
    $entity = \Drupal::entityTypeManager()->getStorage('post')->load($post_id);

    $links = call_user_func(
      [\Drupal::entityTypeManager()->getViewBuilder('post'), __FUNCTION__],
      $post_id,
      'default',
      $entity->language()->getId(),
      !empty($entity->in_preview)
    );

    if (isset($links['post']['#links']['delete'])) {
      $url = Url::fromRoute('social_album.image.delete', [
        'node' => $node_id,
        'post' => $post_id,
        'fid' => $file_id,
      ]);

      if ($url->access()) {
        $links['post']['#links']['delete']['url'] = $url;
      }
      else {
        unset($links['post']['#links']['delete']);
      }
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderLinks'];
  }

}
