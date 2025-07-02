<?php

namespace Drupal\social_like\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_like\Plugin\GraphQL\DataProducer\UserLikesCreated;
use Drupal\votingapi\Entity\Vote;

/**
 * Provides hook related to likes.
 */
final class LikeHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('vote_delete')]
  #[Hook('vote_insert')]
  public function likeCreateDelete(EntityInterface $entity): void {
    if (!$entity instanceof Vote || $entity->bundle() !== 'like') {
      return;
    }
    // Invalidate cache.
    $this->cacheInvalidator->invalidateTags([UserLikesCreated::CID_BASE . $entity->getOwnerId()]);
  }

}
