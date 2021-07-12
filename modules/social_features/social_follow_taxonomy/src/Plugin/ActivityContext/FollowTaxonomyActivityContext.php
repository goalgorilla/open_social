<?php

namespace Drupal\social_follow_taxonomy\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'FollowTaxonomyActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *  id = "follow_taxonomy_activity_context",
 *  label = @Translation("Following taxonomy activity context"),
 * )
 */
class FollowTaxonomyActivityContext extends ActivityContextBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelperService;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\social_group\SocialGroupHelperService $group_helper_service
   *   The group helper service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    ModuleHandlerInterface $module_handler,
    Connection $connection,
    SocialGroupHelperService $group_helper_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->moduleHandler = $module_handler;
    $this->connection = $connection;
    $this->groupHelperService = $group_helper_service;
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
      $container->get('module_handler'),
      $container->get('database'),
      $container->get('social_group.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    // It could happen that a notification has been queued but the account has
    // since been deleted and message author is anonymous.
    if (!empty($data['actor']) && $data['actor'] === 0) {
      return [];
    }

    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node' || $related_entity['target_type'] == 'post') {
        $recipients += $this->getRecipientsWhoFollowTaxonomy($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * List of taxonomy terms.
   */
  public function taxonomyTermsList($entity) {
    $term_ids = social_follow_taxonomy_terms_list($entity);

    return $term_ids;
  }

  /**
   * Returns recipients from followed taxonomies.
   */
  public function getRecipientsWhoFollowTaxonomy(array $related_entity, array $data) {
    $recipients = [];

    $entity = $this->entityTypeManager->getStorage($related_entity['target_type'])
      ->load($related_entity['target_id']);

    if (!empty($entity)) {
      $tids = $this->taxonomyTermsList($entity);
    }

    if (empty($tids)) {
      return [];
    }

    // Get followers.
    $uids = $this->connection->select('flagging', 'f')
      ->fields('f', ['uid'])
      ->condition('flag_id', 'follow_term')
      ->condition('entity_type', 'taxonomy_term')
      ->condition('entity_id', $tids, 'IN')
      ->groupBy('uid')
      ->execute()->fetchCol();

    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    foreach ($users as $recipient) {
      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      // Do not send notification for inactive user.
      if (
        $recipient->isBlocked() ||
        !$recipient->getLastLoginTime()
      ) {
        continue;
      }

      // We don't send notifications to content creator.
      if ($recipient->id() === $entity->getOwnerId()) {
        continue;
      }

      // Check if user have access to view node.
      if (!$this->haveAccessToNode($recipient, $entity->id())) {
        continue;
      }

      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $recipient->id(),
      ];
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    // Check entity type.
    switch ($entity->getEntityTypeId()) {
      case 'node':
      case 'post':
        foreach ($this->getListOfTagsFields() as $field_name) {
          if (
            $entity->hasField($field_name) &&
            !$entity->get($field_name)->isEmpty()
          ) {
            return TRUE;
          }
        }
        return FALSE;
    }
    return FALSE;
  }

  /**
   * Returns list of field names that needs to check for entity validation.
   *
   * @return string[]
   *   List of filed names.
   */
  public function getListOfTagsFields() {
    $fields_to_check = [
      'social_tagging',
    ];
    $this->moduleHandler->alter('social_follow_taxonomy_fields', $fields_to_check);
    return $fields_to_check;
  }

  /**
   * Checks if recipient have access to view related node.
   *
   * @param \Drupal\user\UserInterface $recipient
   *   The user who receives the message.
   * @param string|int $nid
   *   Node ID.
   *
   * @return bool
   *   Returns TRUE if have access.
   */
  protected function haveAccessToNode(UserInterface $recipient, $nid) {
    $query = $this->connection->select('node_field_data', 'nfd');
    $query->leftJoin('node__field_content_visibility', 'nfcv', 'nfcv.entity_id = nfd.nid');
    $query->leftJoin('group_content_field_data', 'gcfd', 'gcfd.entity_id = nfd.nid');
    $or = $query->orConditionGroup();
    $community_access = $or->andConditionGroup()
      ->condition('nfcv.field_content_visibility_value', ['community', 'public'], 'IN')
      ->isNull('gcfd.entity_id');
    $or->condition($community_access);
    // Node visibility by group.
    $memberships = $this->groupHelperService->getAllGroupsForUser($recipient->id());
    if (count($memberships) > 0) {
      $access_by_group = $or->andConditionGroup();
      $access_by_group->condition('nfcv.field_content_visibility_value', ['group', 'community', 'public'], 'IN');
      $access_by_group->condition('gcfd.type', '%-group_node-%', 'LIKE');
      $access_by_group->condition('gcfd.gid', $memberships, 'IN');
      $or->condition($access_by_group);
    }
    $or->isNull('nfcv.entity_id');
    $query->condition($or);
    $query->condition('nfd.nid', $nid);
    $query->groupBy('nfd.nid');
    $query->addExpression('COUNT(*)');
    $nids = $query->execute()->fetchField();

    return !empty($nids);
  }

}
