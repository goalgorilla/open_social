<?php

namespace Drupal\social_post_album;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides lazy builder for content inside modal window.
 *
 * @package Drupal\social_post_album
 */
class LazyRenderer implements TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * LazyRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get post with specific view mode via lazy builder.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|int $post_id
   *   The post ID.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The render array of post.
   */
  public function getPost(string $entity_type, string|int $post_id, string $view_mode): array {
    $post = $this->entityTypeManager->getStorage($entity_type)->load($post_id);
    if ($post !== NULL) {
      return $this->entityTypeManager->getViewBuilder($entity_type)->view($post, $view_mode);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['getPost'];
  }

}
