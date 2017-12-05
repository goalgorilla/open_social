<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'CommentOwnerActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "comment_owner_activity_context",
 *  label = @Translation("Comment owner activity context"),
 * )
 */
class CommentOwnerActivityContext extends ActivityContextBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = ActivityFactory::getActivityRelatedEntity($data);

      /** @var \Drupal\social_post\Entity\PostInterface $post */
      $post = $this->entityTypeManager->getStorage('post')
        ->load($related_entity['target_id']);

      $uids = $this->database->select('comment_field_data', 'c')
        ->distinct()
        ->fields('c', ['uid'])
        ->condition('entity_type', 'post')
        ->condition('entity_id', $related_entity['target_id'])
        ->condition('uid', $post->getOwnerId(), '!=')
        ->condition('uid', $data['actor'], '!=')
        ->execute()
        ->fetchCol();

      foreach ($uids as $uid) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $uid,
        ];
      }
    }

    return $recipients;
  }

}
