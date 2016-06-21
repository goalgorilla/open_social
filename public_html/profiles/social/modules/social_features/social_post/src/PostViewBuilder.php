<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\social_post\Entity\Post;

/**
 * Render controller for posts.
 */
class PostViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {

      $build[$id]['links'] = array(
        '#lazy_builder' => [get_called_class() . '::renderLinks', [
          $entity->id(),
          $view_mode,
          $entity->language()->getId(),
          !empty($entity->in_preview),
        ],
        ],
      );
    }
  }

  /**
   * #lazy_builder callback; builds a post's links.
   *
   * @param string $post_entity_id
   *   The post entity ID.
   * @param string $view_mode
   *   The view mode in which the post entity is being viewed.
   * @param string $langcode
   *   The language in which the post entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the post is currently being previewed.
   *
   * @return array
   *   A renderable array representing the post links.
   */
  public static function renderLinks($post_entity_id, $view_mode, $langcode, $is_in_preview) {
    $links = array(
      '#theme' => 'links',
      '#pre_render' => array('drupal_pre_render_links'),
      '#attributes' => array('class' => array('links', 'inline')),
    );

    if (!$is_in_preview) {
      $entity = Post::load($post_entity_id)->getTranslation($langcode);
      $links['post'] = static::buildLinks($entity, $view_mode);

      // Allow other modules to alter the post links.
      $hook_context = array(
        'view_mode' => $view_mode,
        'langcode' => $langcode,
      );
      \Drupal::moduleHandler()->alter('post_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (Read more) for a post.
   *
   * @param \Drupal\social_post\Entity\Post $entity
   *   The post object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(Post $entity, $view_mode) {
    $links = array();

    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $links['edit'] = array(
        'title' => t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $links['delete'] = array(
        'title' => t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo('delete-form'),
      );
    }

    return array(
      '#theme' => 'links',
      '#links' => $links,
      '#attributes' => array('class' => array('links', 'inline')),
    );
  }

}
