<?php

namespace Drupal\social_topic\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\node\Entity\Node;
use Drupal\social_topic\Plugin\GraphQL\DataProducer\TopicsCreated;

/**
 * Provides hook related to node topics.
 */
final class TopicsHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('node_delete')]
  #[Hook('node_insert')]
  public function eventCreateDelete(EntityInterface $entity): void {
    if (!$entity instanceof Node || $entity->bundle() !== 'topic') {
      return;
    }
    // Invalidate cache.
    $this->cacheInvalidator->invalidateTags([TopicsCreated::CID_BASE . $entity->getOwnerId()]);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function eventUpdate(EntityInterface $entity): void {
    // Using $entity->original, $entity->getOriginal() does not exists here.
    if (!$entity instanceof Node || $entity->bundle() !== 'topic' || !$entity->original instanceof Node) {
      return;
    }
    // Invalidate cache on author change for both.
    if ($entity->original->getOwnerId() !== $entity->getOwnerId()) {
      $this->cacheInvalidator->invalidateTags([TopicsCreated::CID_BASE . $entity->getOwnerId()]);
      $this->cacheInvalidator->invalidateTags([TopicsCreated::CID_BASE . $entity->original->getOwnerId()]);
    }
  }

}
