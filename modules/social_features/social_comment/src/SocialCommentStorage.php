<?php

namespace Drupal\social_comment;

use Drupal\comment\CommentStorage;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for comments.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class SocialCommentStorage extends CommentStorage implements SocialCommentStorageInterface {

  /**
   * The JSON serialization.
   */
  private SerializationInterface $serialization;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_info
  ) {
    $instance = parent::createInstance($container, $entity_info);

    $instance->serialization = $container->get('serialization.json');

    return $instance;
  }

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

    return $this->loadThread(
      $entity,
      $this->serialization->encode($items),
      $mode,
      $comments_per_page,
      $pager_id,
    );
  }

}
