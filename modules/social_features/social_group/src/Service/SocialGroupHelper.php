<?php

namespace Drupal\social_group\Service;

use Drupal\comment\CommentInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\social_post\Entity\PostInterface;

/**
 * Defines the helper service.
 *
 * @package Drupal\social_group\Service
 */
class SocialGroupHelper implements SocialGroupHelperInterface {

  use StringTranslationTrait;

  /**
   * A cache of groups that have been matched to entities.
   *
   * @var array
   */
  protected $cache;

  /**
   * The database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Connection $connection,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $translation,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->database = $connection;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupFromEntity(array $entity, bool $read_cache = TRUE) {
    $gid = NULL;

    // Comments can have groups based on what the comment is posted on so the
    // cache type differs from what we later use to fetch the group.
    $cache_type = $entity['target_type'];
    $cache_id = $entity['target_id'];

    if (
      $read_cache &&
      is_array($this->cache) &&
      is_array($this->cache[$cache_type]) &&
      isset($this->cache[$cache_type][$cache_id])
    ) {
      return $this->cache[$cache_type][$cache_id];
    }

    // Special cases for comments.
    // Returns the entity to which the comment is attached.
    if ($entity['target_type'] === 'comment') {
      $comment = $this->entityTypeManager->getStorage('comment')
        ->load($entity['target_id']);

      if (
        $comment instanceof CommentInterface &&
        ($commented_entity = $comment->getCommentedEntity()) !== NULL
      ) {
        $entity['target_type'] = $commented_entity->getEntityTypeId();
        $entity['target_id'] = $commented_entity->id();
      }
    }
    elseif ($entity['target_type'] === 'post') {
      $post = $this->entityTypeManager->getStorage('post')
        ->load($entity['target_id']);

      if ($post instanceof PostInterface) {
        $recipient_group = $post->get('field_recipient_group')->getValue();
        if (!empty($recipient_group)) {
          $gid = $recipient_group['0']['target_id'];
        }
      }
    }
    elseif ($entity['target_type'] === 'node') {
      $node = $this->entityTypeManager->getStorage('node')
        ->load($entity['target_id']);

      // Try to load the entity.
      if ($node instanceof ContentEntityInterface) {
        // Try to load group content from entity.
        if ($group_contents = GroupContent::loadByEntity($node)) {
          // Set the group id.
          $gid = reset($group_contents)->getGroup()->id();
        }
      }
    }
    elseif ($entity['target_type'] === 'group_content') {
      $group_content = $this->entityTypeManager->getStorage('group_content')
        ->load($entity['target_id']);

      // Try to load the entity.
      if ($group_content instanceof GroupContentInterface) {
        // Get group id.
        $gid = $group_content->getGroup()->id();
      }
    }

    // Cache the group id for this entity to optimise future calls.
    $this->cache[$cache_type][$cache_id] = $gid;

    return $gid;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultGroupVisibility(string $type) {
    $visibility = &drupal_static(__FUNCTION__ . $type);

    if (empty($visibility)) {
      switch ($type) {
        case 'closed_group':
          $visibility = 'group';
          break;

        case 'open_group':
          $visibility = 'community';
          break;

        case 'public_group':
          $visibility = 'public';
          break;

        default:
          $visibility = NULL;
      }

      \Drupal::moduleHandler()
        ->alter('social_group_default_visibility', $visibility, $type);
    }

    return $visibility;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCurrentGroupMembers() {
    $cache = &drupal_static(__FUNCTION__, []);

    if (!empty($cache)) {
      return $cache;
    }

    $group = _social_group_get_current_group();
    if ($group instanceof GroupInterface) {
      $memberships = $group->getMembers();
      foreach ($memberships as $member) {
        $cache[] = $member->getUser()->id();
      }
    }

    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllGroupsForUser(int $uid) {
    $groups = &drupal_static(__FUNCTION__);

    // Get the memberships for the user if they aren't known yet.
    if (!isset($groups[$uid])) {
      // We need to get all group memberships,
      // GroupContentType::loadByEntityTypeId('user'); will also return
      // requests and invites for a given user entity.
      $group_content_types = GroupContentType::loadByContentPluginId('group_membership');
      $group_content_types = array_keys($group_content_types);

      $query = $this->database->select('group_content_field_data', 'gcfd');
      $query->addField('gcfd', 'gid');
      $query->condition('gcfd.entity_id', (string) $uid);
      $query->condition('gcfd.type', $group_content_types, 'IN');

      if (($result = $query->execute()) !== NULL) {
        $groups[$uid] = array_keys($result->fetchAllAssoc('gid'));
      }
    }

    return $groups[$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function countGroupMembershipsForUser(string $uid): int {
    $count = &drupal_static(__FUNCTION__);

    // Get the count of memberships for the user if they aren't known yet.
    if (!isset($count[$uid])) {
      $hidden_types = [];
      $this->moduleHandler->alter('social_group_hide_types', $hidden_types);

      $group_content_types = GroupContentType::loadByEntityTypeId('user');
      $group_content_types = array_keys($group_content_types);
      $query = $this->database->select('group_content_field_data', 'gcfd');
      $query->addField('gcfd', 'gid');
      $query->condition('gcfd.entity_id', $uid);
      $query->condition('gcfd.type', $group_content_types, 'IN');
      if (!empty($hidden_types)) {
        foreach ($hidden_types as $group_type) {
          $query->condition('gcfd.type', '%' . $this->database->escapeLike($group_type) . '%', 'NOT LIKE');
        }
      }
      // We need to add another like for the fact that we have more plugins
      // than memberships for a User, like request or invite which are not
      // group memberships yet.
      $query->condition('gcfd.type', '%group_membership', 'LIKE');
      // Add a query tag for other modules to alter, this query.
      $query->addTag('count_memberships_for_user');

      if (($result = $query->countQuery()->execute()) !== NULL) {
        $count[$uid] = $result->fetchField();
      }
    }

    return $count[$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsToAddUrl(AccountInterface $account) {
    $url = NULL;
    $user_can_create_groups = [];
    // Get all available group types.
    foreach (GroupType::loadMultiple() as $group_type) {
      // When the user has permission to create a group of the current type, add
      // this to the create group array.
      if ($account->hasPermission('create ' . $group_type->id() . ' group')) {
        $user_can_create_groups[$group_type->id()] = $group_type;
      }

      if (count($user_can_create_groups) > 1) {
        break;
      }
    }

    // There's just one group this user can create.
    if (count($user_can_create_groups) === 1) {
      // When there is only one group allowed, add create the url to create a
      // group of this type.
      $allowed_group_type = reset($user_can_create_groups);
      /** @var \Drupal\group\Entity\Group $allowed_group_type */
      $url = Url::fromRoute('entity.group.add_form', [
        'group_type' => $allowed_group_type->id(),
      ]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function addMemberFormField() {
    return [
      '#title' => $this->t('Find people by name or email address'),
      '#type' => 'select2',
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#autocomplete' => TRUE,
      '#select2' => [
        'placeholder' => $this->t('Jane Doe'),
        'tokenSeparators' => [',', ';'],
      ],
      '#selection_handler' => 'social',
      '#target_type' => 'user',
      '#element_validate' => ['_social_group_unique_members'],
    ];
  }

}
