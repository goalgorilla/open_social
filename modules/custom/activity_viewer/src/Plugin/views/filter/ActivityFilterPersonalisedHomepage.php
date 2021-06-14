<?php

namespace Drupal\activity_viewer\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters activity for a personalised homepage.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_filter_personalised_homepage")
 */
class ActivityFilterPersonalisedHomepage extends FilterPluginBase {

  /**
   * The group helper.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelper;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_group\SocialGroupHelperService $group_helper
   *   The group helper.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SocialGroupHelperService $group_helper,
    Connection $connection,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->groupHelper = $group_helper;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('social_group.helper_service'),
      $container->get('database'),
      $container->get('module_handler')
    );
  }

  /**
   * Not exposable.
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Filters out activity items the user is not allowed to see.
   *
   * The access to the activity items may be limited by the following:
   *  1. Value in field_visibility_value on a Post entity.
   *  2. Node access grants (this includes the field_node_visibility_value and
   *     the nodes in a closed group)
   *  3. The comment or post is posted in a (closed) group.
   *
   * In addition to the condition used in this filter there may be some other
   * filters active in the given view (e.g. destination).
   *
   * Probably want to extend this to entity access based on the node grant
   * system when this is implemented.
   * See https://www.drupal.org/node/777578
   */
  public function query() {
    $account = $this->view->getUser();
    $skip_roles = [
      'administrator',
      'contentmanager',
      'sitemanager',
    ];
    $hide_from_view = $nids = $pids = $cids = [];

    // Skip filter for users that have full access to the site content.
    if (!empty(array_intersect($skip_roles, $account->getRoles()))) {
      return;
    }

    $this->ensureMyTable();
    $group_memberships = $this->groupHelper->getAllGroupsForUser($account->id());
    /** @var \Drupal\views\Plugin\views\query\Sql $filter_query */
    $filter_query = $this->query;
    $filter_query->addTable('activity__field_activity_entity');
    $filter_query->addTable('activity__field_activity_recipient_group');
    $filter_query->addTable('activity__field_activity_recipient_user');

    // Add queries.
    $and_wrapper = new Condition('AND');
    $or = new Condition('OR');

    // Nodes: retrieve all the nodes to which the user has access.
    if ($account->hasPermission('access content')) {
      $nids = $this->getAvailableNodeIds($account, $group_memberships);
      if (!empty($nids)) {
        $node_access = $or->andConditionGroup()
          ->condition('activity__field_activity_entity.field_activity_entity_target_type', 'node')
          ->condition('activity__field_activity_entity.field_activity_entity_target_id', $nids, 'IN');
        $or->condition($node_access);
      }
      else {
        $hide_from_view[] = 'node';
      }
    }

    // Posts: retrieve all the posts to which the user has access.
    $pids = $this->getAvailablePostIds($account, $group_memberships);
    if (!empty($pids)) {
      $post_access = $or->andConditionGroup()
        ->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post')
        ->condition('activity__field_activity_entity.field_activity_entity_target_id', $pids, 'IN');
      $or->condition($post_access);
    }
    else {
      $hide_from_view[] = 'post';
    }

    // Comments: retrieve comments the user has access to.
    if ($account->hasPermission('access comments')) {
      $cids = $this->getAvailableCommentIds($nids, $pids);
      if (!empty($cids)) {
        $comments_access = $or->andConditionGroup()
          ->condition('activity__field_activity_entity.field_activity_entity_target_type', 'comment')
          ->condition('activity__field_activity_entity.field_activity_entity_target_id', $cids, 'IN');
        $or->condition($comments_access);
      }
      else {
        $hide_from_view[] = 'comment';
      }
    }

    if (!empty($hide_from_view)) {
      $and_wrapper->condition('activity__field_activity_entity.field_activity_entity_target_type', $hide_from_view, 'NOT IN');
    }

    // Lets add all the or conditions to the Views query.
    if (!empty($or->conditions()[0])) {
      $and_wrapper->condition($or);
    }

    // Only activities which don't have direct user and group.
    if ($account->isAnonymous()) {
      $an_access = new Condition('AND');
      $an_user_target = new Condition('OR');
      $an_user_target->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', '0');
      $an_user_target->isNull('activity__field_activity_recipient_user.field_activity_recipient_user_target_id');
      $an_access->condition($an_user_target);

      $an_access->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id');
      $and_wrapper->condition($an_access);
    }
    else {
      // Only activities which targeted to current user.
      $lu_access = new Condition('AND');
      $lu_user_target = new Condition('OR');
      $lu_user_target->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', (string) $account->id());
      $lu_user_target->isNull('activity__field_activity_recipient_user.field_activity_recipient_user_target_id');
      $lu_access->condition($lu_user_target);

      // Only activities which targeted to current user's groups.
      $lu_group_target = new Condition('OR');
      if (!empty($group_memberships)) {
        $lu_group_target->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $group_memberships, 'IN');
      }
      $lu_group_target->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id');
      $lu_access->condition($lu_group_target);

      $and_wrapper->condition($lu_access);
    }

    if (!empty($and_wrapper->conditions()[0])) {
      $filter_query->addWhere('visibility', $and_wrapper);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * Gets list of node IDs to which user has access.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param array $memberships
   *   List of user memberships.
   *
   * @return array
   *   List of node IDs.
   */
  protected function getAvailableNodeIds(AccountInterface $user, array $memberships) {
    $query = $this->connection->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);
    $query->leftJoin('node__field_content_visibility', 'nfcv', 'nfcv.entity_id = nfd.nid');
    $query->leftJoin('group_content_field_data', 'gcfd', 'gcfd.entity_id = nfd.nid');
    $or = $query->orConditionGroup();
    if ($user->isAuthenticated()) {
      // Nodes community visibility.
      $community_access = $or->andConditionGroup()
        ->condition('nfcv.field_content_visibility_value', ['community', 'public'], 'IN')
        ->isNull('gcfd.entity_id');
      $or->condition($community_access);

      // Node visibility by group.
      if (count($memberships) > 0) {
        $access_by_group = $or->andConditionGroup();
        $access_by_group->condition('nfcv.field_content_visibility_value', ['group', 'community', 'public'], 'IN');
        $access_by_group->condition('gcfd.type', '%-group_node-%', 'LIKE');
        $access_by_group->condition('gcfd.gid', $memberships, 'IN');
        $or->condition($access_by_group);
      }
    }
    else {
      // Public nodes without group.
      $anonymous_access = $or->andConditionGroup()
        ->condition('nfcv.field_content_visibility_value', 'public')
        ->isNull('gcfd.entity_id');
      $or->condition($anonymous_access);
    }
    $or->isNull('nfcv.entity_id');
    $query->condition($or);

    // Alter query for custom conditions.
    $this->moduleHandler->alter('activity_viewer_available_nodes_query', $query, $user);

    // Check node status and user access to it.
    $node_status = ['1'];
    if ($user->hasPermission('view any unpublished content')) {
      $node_status[] = '0';
    }
    $query->condition('nfd.status', $node_status, 'IN');
    $nids = $query->execute()->fetchCol();

    return array_unique($nids);
  }

  /**
   * Gets list of post IDs to which user has access.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param array $memberships
   *   List of user memberships.
   *
   * @return array
   *   List of post IDs.
   */
  protected function getAvailablePostIds(AccountInterface $user, array $memberships) {
    $query = $this->connection->select('post_field_data', 'pfd');
    $query->fields('pfd', ['id']);
    $query->leftJoin('post__field_visibility', 'pfv', 'pfv.entity_id = pfd.id');
    $query->leftJoin('post__field_recipient_group', 'pfrg', 'pfrg.entity_id = pfd.id');
    $or = $query->orConditionGroup();
    if ($user->isAuthenticated()) {
      // Posts for authenticated users if has permission.
      if ($user->hasPermission('view community posts')) {
        // Posts community visibility.
        $community_access = $or->andConditionGroup()
          ->condition('pfv.field_visibility_value', ['0', '1', '2'], 'IN')
          ->isNull('pfrg.entity_id');
        $or->condition($community_access);
      }

      // Posts related to the group where the user is a member.
      if (count($memberships) > 0) {
        $access_by_group = $or->andConditionGroup();
        $access_by_group->condition('pfv.field_visibility_value', ['0', '1', '2', '3'], 'IN');
        $access_by_group->condition('pfrg.field_recipient_group_target_id', $memberships, 'IN');
        $or->condition($access_by_group);
      }
    }
    else {
      // Public posts or do not have visibility settings.
      if ($user->hasPermission('view public posts')) {
        $anonymous_access = $or->andConditionGroup()
          ->condition('pfv.field_visibility_value', '1')
          ->isNull('pfrg.entity_id');
        $or->condition($anonymous_access);
      }
    }
    $or->isNull('pfv.entity_id');
    $query->condition($or);

    // Alter query for custom conditions.
    $this->moduleHandler->alter('activity_viewer_available_posts_query', $query, $user);

    // Check posts status and user access to it.
    $post_status = ['1'];
    if ($user->hasPermission('view unpublished post entities')) {
      $post_status[] = '0';
    }
    $query->condition('pfd.status', $post_status, 'IN');

    $pids = $query->execute()->fetchCol();

    return array_unique($pids);
  }

  /**
   * Gets list of comment IDs to which user has access.
   *
   * @param array $node_ids
   *   List of node IDs.
   * @param array $post_ids
   *   List of post IDs.
   *
   * @return array
   *   List of comment IDs.
   */
  protected function getAvailableCommentIds(array $node_ids, array $post_ids) {
    $query = $this->connection->select('comment_field_data', 'cfd');
    $query->fields('cfd', ['cid']);
    $or = $query->orConditionGroup();

    // Return empty array if any nodes or posts are not available.
    if (empty($node_ids) && empty($post_ids)) {
      return [];
    }

    // Comments related to available nodes.
    if (!empty($node_ids)) {
      $node_access = $or->andConditionGroup();
      $node_access->condition('entity_type', 'node');
      $node_access->condition('entity_id', $node_ids, 'IN');
      $or->condition($node_access);
    }

    // Comments related to available posts.
    if (!empty($post_ids)) {
      $post_access = $or->andConditionGroup();
      $post_access->condition('entity_type', 'post');
      $post_access->condition('entity_id', $post_ids, 'IN');
      $or->condition($post_access);
    }
    if (!empty($or->conditions()[0])) {
      $query->condition($or);
    }
    $query->condition('cfd.status', '1');

    $cids = $query->execute()->fetchCol();

    return array_unique($cids);
  }

}
