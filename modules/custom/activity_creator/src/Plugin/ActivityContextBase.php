<?php
/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityContextBase.
 */

namespace Drupal\activity_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_post\Entity\Post;
use Drupal\node\Entity\Node;

/**
 * Base class for Activity context plugin plugins.
 */
abstract class ActivityContextBase extends PluginBase implements ActivityContextInterface, ContainerFactoryPluginInterface {

  /**
   * Entity query.
   *
   * @var \Drupal\Core\Entity\Query\Sql\QueryFactory
   */
  private $entity_query;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entity_type_manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entity_query = $entity_query;
    $this->entity_type_manager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    return $recipients;
  }

  public function isValidEntity($entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientsFromPost(array $referenced_entity) {
    $recipients = [];

    $post = Post::load($referenced_entity['target_id']);
    $recipient_user = $post->get('field_recipient_user')->getValue();
    if (!empty($recipient_user)) {
      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $recipient_user['0']['target_id'],
      ];
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientsFromNode(array $referenced_entity) {
    $recipients = [];

    if ($referenced_entity['target_type'] === 'node') {
      $node = Node::load($referenced_entity['target_id']);
      $recipient_user_id = $node->getOwnerId();
      if (!empty($recipient_user_id)) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $recipient_user_id,
        ];
      }
    }

    return $recipients;
  }

}
