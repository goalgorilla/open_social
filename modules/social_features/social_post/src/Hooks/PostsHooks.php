<?php

namespace Drupal\social_post\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_post\Entity\PostInterface;
use Drupal\social_post\Plugin\GraphQL\DataProducer\UserPostsCreated;

/**
 * Provides hook related to posts.
 */
final class PostsHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('post_delete')]
  #[Hook('post_insert')]
  public function postCreateDelete(EntityInterface $entity): void {
    if (!$entity instanceof PostInterface) {
      return;
    }
    // Invalidate cache.
    $this->cacheInvalidator->invalidateTags([UserPostsCreated::CID_BASE . $entity->getOwnerId()]);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('post_update')]
  public function postUpdate(EntityInterface $entity): void {
    // Using $entity->original, $entity->getOriginal() does not exists here.
    if (!$entity instanceof PostInterface || empty($entity->original) || !$entity->original instanceof PostInterface) {
      return;
    }
    // Invalidate cache on author change for both.
    if ($entity->original->getOwnerId() !== $entity->getOwnerId()) {
      $this->cacheInvalidator->invalidateTags([UserPostsCreated::CID_BASE . $entity->getOwnerId()]);
      $this->cacheInvalidator->invalidateTags([UserPostsCreated::CID_BASE . $entity->original->getOwnerId()]);
    }
  }

}
