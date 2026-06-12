<?php

namespace Drupal\social_comment\Entity\Access;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessHandlerBase;
use Drupal\social_comment\HiddenCommentFieldAccessInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Controls query access for comment entities.
 *
 * For 'view' with "access comments", comment list access is restricted as
 * follows:
 *
 * - Comments on nodes: restricted to nodes the account can view. A node
 *   query with accessCheck(TRUE) runs NodeEntityQueryAlter (social_node) and
 *   NODE_QUERY_ACCESS_ALTER subscribers (entity access by field, e.g.
 *   field_content_visibility; group content visibility; unpublished handling).
 *   So comments on group-only or visibility-restricted content appear only
 *   when the user has access to the commented node.
 *
 * - Comments on other entity types (e.g. post): currently not restricted by
 *   parent-entity access. Per-entity access (e.g. post query access) can be
 *   added later in the same way as for nodes.
 *
 * - Comments on nodes with a comment field status of Hidden are excluded for
 *   accounts with revoked view on hidden fields (HiddenCommentFieldAccess).
 *
 * @see \Drupal\entity\QueryAccess\QueryAccessHandler
 * @see \Drupal\social_node\QueryAccess\NodeEntityQueryAlter
 * @see \Drupal\social_node\EventSubscriber\NodeQueryAccessAlterSubscriber
 * @see \Drupal\social_group\EventSubscriber\NodeQueryAccessAlterSubscriber
 */
class CommentQueryAccessHandler extends QueryAccessHandlerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Hidden comment field access helper.
   *
   * @var \Drupal\social_comment\HiddenCommentFieldAccessInterface
   */
  protected HiddenCommentFieldAccessInterface $hiddenCommentFieldAccess;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * Constructs a CommentQueryAccessHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\social_comment\HiddenCommentFieldAccessInterface $hidden_comment_field_access
   *   Hidden comment field access helper.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeBundleInfoInterface $bundle_info, EventDispatcherInterface $event_dispatcher, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, HiddenCommentFieldAccessInterface $hidden_comment_field_access, Connection $database) {
    parent::__construct($entity_type, $bundle_info, $event_dispatcher, $current_user);
    $this->entityTypeManager = $entity_type_manager;
    $this->hiddenCommentFieldAccess = $hidden_comment_field_access;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.bundle.info'),
      $container->get('event_dispatcher'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('social_comment.hidden_comment_field_access'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConditions($operation, AccountInterface $account) {
    $entity_type_id = $this->entityType->id();
    $has_owner = $this->entityType->entityClassImplements(EntityOwnerInterface::class);
    $has_published = $this->entityType->entityClassImplements(EntityPublishedInterface::class);
    // Guard against broken/incomplete entity type definitions.
    if ($has_owner && !$this->entityType->hasKey('owner')) {
      throw new \RuntimeException(sprintf('The "%s" entity type did not define a "owner" key.', $entity_type_id));
    }
    if ($has_published && !$this->entityType->hasKey('published')) {
      throw new \RuntimeException(sprintf('The "%s" entity type did not define a "published" key', $entity_type_id));
    }

    if ($account->hasPermission("administer comments")) {
      // The user has full access to all operations, no conditions needed.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      return $conditions;
    }

    if ($has_owner) {
      $entity_conditions = $this->buildEntityOwnerConditions($operation, $account);
    }
    else {
      $entity_conditions = $this->buildEntityConditions($operation, $account);
    }

    $conditions = NULL;
    if ($operation == 'view' && $has_published) {
      $published_key = $this->entityType->getKey('published');
      $published_conditions = NULL;

      if ($entity_conditions) {
        // Restrict the existing conditions to published entities only.
        $published_conditions = new ConditionGroup('AND');
        $published_conditions->addCacheContexts(['user.permissions']);
        $published_conditions->addCondition($entity_conditions);
        if ($published_key !== FALSE) {
          $published_conditions->addCondition($published_key, '1');
        }
      }

      if ($published_conditions) {
        $conditions = $published_conditions;
      }
    }
    else {
      $conditions = $entity_conditions;
    }

    if (!$conditions) {
      // The user doesn't have access to any entities.
      // Falsify the query to ensure no results are returned.
      $conditions = new ConditionGroup('OR');
      $conditions->addCacheContexts(['user.permissions']);
      $conditions->alwaysFalse();
    }

    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityOwnerConditions($operation, AccountInterface $account) {
    $conditions = new ConditionGroup('OR');
    $conditions->addCacheContexts(['user.permissions']);
    if ($account->hasPermission("access comments")) {
      if ($operation === 'view') {
        // Comments on nodes: restrict to nodes the account can view (node query
        // with accessCheck(TRUE) runs the full node access stack).
        $node_access = $this->getNodeCommentAccessData($account);
        if ($node_access['accessible_nids'] !== []) {
          $node_conditions = new ConditionGroup('AND');
          $node_conditions->addCacheContexts(['user.permissions', 'user']);
          $node_cache_tags = ['node_list'];
          foreach ($node_access['accessible_nids'] as $nid) {
            $node_cache_tags[] = 'node:' . $nid;
          }
          foreach (array_keys($node_access['hidden_exclusions']) as $nid) {
            $node_cache_tags[] = 'node:' . $nid;
          }
          $node_conditions->addCacheTags(array_values(array_unique($node_cache_tags)));
          $node_conditions->addCondition('entity_type', 'node');
          $node_conditions->addCondition('entity_id', $node_access['accessible_nids']);
          $this->addHiddenCommentFieldExclusions($node_conditions, $node_access['forbidden_cids']);
          $conditions->addCondition($node_conditions);
        }
        // Comments on other entity types (e.g. post): allow for now; per-entity
        // access (e.g. post query access) can be added later.
        $conditions->addCondition('entity_type', 'node', '<>');
      }
      return $conditions->count() ? $conditions : NULL;
    }

    return $conditions->count() ? $conditions : NULL;
  }

  /**
   * Per-request cache of node comment query access data per account.
   *
   * @var array<int, array{accessible_nids: int[], hidden_exclusions: array<int, string[]>, forbidden_cids: int[]}>
   */
  protected static array $nodeCommentAccessCache = [];

  /**
   * Clears per-request node comment query access cache.
   */
  public static function resetNodeCommentAccessCache(): void {
    static::$nodeCommentAccessCache = [];
  }

  /**
   * Excludes comments on hidden node/field pairs from list queries.
   *
   * @param \Drupal\entity\QueryAccess\ConditionGroup $node_conditions
   *   The node comment condition group to extend.
   * @param int[] $forbidden_cids
   *   Comment IDs attached to hidden fields the account may not view.
   */
  protected function addHiddenCommentFieldExclusions(ConditionGroup $node_conditions, array $forbidden_cids): void {
    if ($forbidden_cids === []) {
      return;
    }

    $node_conditions->addCondition('cid', $forbidden_cids, 'NOT IN');
  }

  /**
   * Returns comment IDs attached to hidden node/field pairs.
   *
   * @param array<int, string[]> $hidden_exclusions
   *   Node ID to excluded comment field names.
   *
   * @return int[]
   *   Comment IDs that must be excluded from list queries.
   */
  protected function getForbiddenHiddenCommentIds(array $hidden_exclusions): array {
    if ($hidden_exclusions === []) {
      return [];
    }

    $query = $this->database->select('comment_field_data', 'cfd');
    $query->addField('cfd', 'cid');
    $query->condition('cfd.entity_type', 'node');
    $or = $query->orConditionGroup();
    foreach ($hidden_exclusions as $nid => $field_names) {
      foreach ($field_names as $field_name) {
        $and = $query->andConditionGroup();
        $and->condition('cfd.entity_id', $nid);
        $and->condition('cfd.field_name', $field_name);
        $or->condition($and);
      }
    }
    $query->condition($or);

    $result = $query->execute();
    if ($result === NULL) {
      return [];
    }

    return array_values(array_map('intval', $result->fetchCol()));
  }

  /**
   * Returns accessible node IDs and hidden-field exclusions for comment lists.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return array{accessible_nids: int[], hidden_exclusions: array<int, string[]>, forbidden_cids: int[]}
   *   Accessible node IDs, field exclusions, and forbidden comment IDs.
   */
  protected function getNodeCommentAccessData(AccountInterface $account): array {
    $uid = (int) $account->id();
    if (isset(static::$nodeCommentAccessCache[$uid])) {
      return static::$nodeCommentAccessCache[$uid];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $accessible_nids = array_values(array_map('intval', $storage->getQuery()
      ->accessCheck(TRUE)
      ->execute()));

    $hidden_exclusions = $accessible_nids === []
      ? []
      : $this->hiddenCommentFieldAccess->getExcludedHiddenFieldsByNid($account, $accessible_nids);

    static::$nodeCommentAccessCache[$uid] = [
      'accessible_nids' => $accessible_nids,
      'hidden_exclusions' => $hidden_exclusions,
      'forbidden_cids' => $this->getForbiddenHiddenCommentIds($hidden_exclusions),
    ];

    return static::$nodeCommentAccessCache[$uid];
  }

}
