<?php

namespace Drupal\social_comment\Hooks;

use Drupal\comment\CommentInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_comment\Plugin\GraphQL\DataProducer\CommentsCreated;

/**
 * Provides hook related to comments.
 */
final class CommentsHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('comment_delete')]
  #[Hook('comment_insert')]
  public function commentCreateDelete(EntityInterface $entity): void {
    if (!$entity instanceof CommentInterface) {
      return;
    }
    // Invalidate cache.
    $this->cacheInvalidator->invalidateTags([CommentsCreated::CID_BASE . $entity->getOwnerId()]);
  }

}
