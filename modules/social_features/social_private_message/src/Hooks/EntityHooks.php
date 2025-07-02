<?php

namespace Drupal\social_private_message\Hooks;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\social_private_message\Plugin\GraphQL\DataProducer\PrivateMessageSent;

/**
 * Provides hook related to the private_message.
 */
final class EntityHooks {

  /**
   * Construct for the hux.
   */
  public function __construct(protected CacheTagsInvalidatorInterface $cacheInvalidator) {}

  /**
   * Implements hook_ENTITY_TYPE_delete() and hook_ENTITY_TYPE_create().
   */
  #[Hook('private_message_delete')]
  #[Hook('private_message_insert')]
  public function messageCreateDelete(EntityInterface $entity): void {
    if (!$entity instanceof PrivateMessage) {
      return;
    }
    // Invalidate cache.
    $this->cacheInvalidator->invalidateTags([PrivateMessageSent::CID_BASE . $entity->getOwnerId()]);
  }

}
