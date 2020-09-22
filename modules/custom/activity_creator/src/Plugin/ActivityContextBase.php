<?php

namespace Drupal\activity_creator\Plugin;

use Drupal\activity_creator\ActivityFactory;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Activity context plugin plugins.
 */
abstract class ActivityContextBase extends PluginBase implements ActivityContextInterface, ContainerFactoryPluginInterface {

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\Sql\QueryFactory
   */
  private $entityQuery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The activity factory service.
   *
   * @var \Drupal\activity_creator\ActivityFactory
   */
  protected $activityFactory;

  /**
   * ActivityContextBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\Sql\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\activity_creator\ActivityFactory $activity_factory
   *   The activity factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->activityFactory = $activity_factory;
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
      $container->get('activity_creator.activity_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    return TRUE;
  }

  /**
   * Returns recipients from post.
   *
   * @param array $referenced_entity
   *   The referenced entity.
   *
   * @return array
   *   An associative array of recipients, containing the following key-value
   *   pairs:
   *   - target_type: The entity type ID.
   *   - target_id: The entity ID.
   */
  public function getRecipientsFromPost(array $referenced_entity) {
    $recipients = [];

    $post = $this->entityTypeManager->getStorage('post')
      ->load($referenced_entity['target_id']);

    if ($post !== NULL && !$post->field_recipient_user->isEmpty()) {
      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $post->field_recipient_user->target_id,
      ];
    }

    return $recipients;
  }

}
