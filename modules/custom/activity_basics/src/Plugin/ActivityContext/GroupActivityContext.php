<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\group\Entity\GroupContent;
use Drupal\social_group\SocialGroupHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "group_activity_context",
 *   label = @Translation("Group activity context"),
 * )
 */
class GroupActivityContext extends ActivityContextBase {

  /**
   * The group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $grouphelperService;

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
   * @param \Drupal\social_group\SocialGroupHelperService $grouphelper_service
   *   The group helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    SocialGroupHelperService $grouphelper_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->grouphelperService = $grouphelper_service;
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
      $container->get('activity_creator.activity_factory'),
      $container->get('social_group.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {

    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {

      $referenced_entity = $data['related_object']['0'];

      if ($gid = $this->grouphelperService->getGroupFromEntity($referenced_entity, FALSE)) {
        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $gid,
        ];
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    // Special cases for comments.
    if ($entity->getEntityTypeId() === 'comment') {
      // Returns the entity to which the comment is attached.
      $entity = $entity->getCommentedEntity();
    }

    if (!isset($entity)) {
      return FALSE;
    }

    // Check if it's placed in a group (regardless off content type).
    if (GroupContent::loadByEntity($entity)) {
      return TRUE;
    }

    if ($entity->getEntityTypeId() === 'post') {
      if (!$entity->field_recipient_group->isEmpty()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
