<?php

namespace Drupal\social_post_photo;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * {@inheritdoc}
 */
class LazyRenderer {

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
   */
  public function getPost($entity_type, $post_id, $view_mode) {
    $post = $this->entityTypeManager->getStorage($entity_type)->load($post_id);
    return $this->entityTypeManager->getViewBuilder($entity_type)->view($post, $view_mode);
  }

}
