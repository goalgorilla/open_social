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
  protected $entityTypeManager;

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
  public function getPost($entity_type, $post_id, $view_mode) {
    $post = $this->entityTypeManager->getStorage($entity_type)->load($post_id);
    return $this->entityTypeManager->getViewBuilder($entity_type)->view($post, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['getPost'];
  }

}
