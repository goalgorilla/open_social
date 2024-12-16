<?php

namespace Drupal\social_album\Plugin\views\field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Url;
use Drupal\social_post\PostViewBuilder;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Renders all operations links for a post.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_album_post_operations")
 */
class SocialAlbumEntityOperations extends EntityOperations {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function render(ResultRow $values): MarkupInterface|array|string {
    $value = NULL;
    foreach (get_object_vars($values) as $key => $val) {
      if (str_ends_with($key, '_delta')) {
        $value = $val;
        break;
      }
    }

    if ($value === NULL) {
      return [];
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity($values);

    $field_item = $entity->get('field_post_image')->get($value);
    $target_id = NULL;
    if ($field_item instanceof FieldItemInterface && isset($field_item->target_id)) {
      $target_id = $field_item->target_id;
    }

    return [
      '#lazy_builder' => [
        [$this, 'renderLinks'],
        [
          $values->_entity->id(),
          $entity->id(),
          $target_id,
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function renderLinks($node_id, $post_id, $file_id) {
    $entity = \Drupal::entityTypeManager()->getStorage('post')->load($post_id);

    if ($entity === NULL) {
      return [];
    }

    $links = PostViewBuilder::renderLinks((string) $post_id, 'default', $entity->language()->getId(), !empty($entity->in_preview));

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
