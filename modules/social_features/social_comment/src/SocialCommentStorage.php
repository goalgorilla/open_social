<?php

namespace Drupal\social_comment;

use Drupal\comment\CommentStorage;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the storage handler class for comments.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class SocialCommentStorage extends CommentStorage implements SocialCommentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadFormatterThread(
    string $formatter,
    EntityInterface $entity,
    string $field_name,
    int $mode,
    int $comments_per_page,
    int $pager_id,
    string $order,
    int $limit
  ): array {
    $items = [
      'field_name' => $field_name,
      'formatter' => $formatter,
      'order' => $order,
      'limit' => $limit,
    ];

    return parent::loadThread(
      $entity,
      Json::encode($items),
      $mode,
      $comments_per_page,
      $pager_id,
    );
  }

}
