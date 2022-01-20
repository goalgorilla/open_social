<?php

namespace Drupal\social_comment\Entity;

use Drupal\comment\Entity\Comment as CommentBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\social_core\EntityUrlLanguageTrait;

/**
 * Provides a Comment entity that has links that work with different languages.
 */
class Comment extends CommentBase {
  use EntityUrlLanguageTrait;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities): void {
    parent::postDelete($storage, $entities);
    // Always invalidate the cache tag for the commented entity.
    /** @var \Drupal\comment\CommentInterface $entity */
    foreach ($entities as $entity) {
      if ($commented_entity = $entity->getCommentedEntity()) {
        Cache::invalidateTags($commented_entity->getCacheTagsToInvalidate());
      }
    }
  }

}
