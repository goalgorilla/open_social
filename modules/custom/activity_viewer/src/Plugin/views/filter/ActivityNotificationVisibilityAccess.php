<?php

namespace Drupal\activity_viewer\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Drupal\Core\Database\Query\Condition;

/**
 * Filters activity based on visibility settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_notification_visibility_access")
 */
class ActivityNotificationVisibilityAccess extends FilterPluginBase {

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

    $open_groups = [];
    $group_memberships = [];
    if (\Drupal::moduleHandler()->moduleExists('social_group')) {
      // TODO: This creates a dependency on Social Group which shouldn't exist,
      // this access logic should be in that module instead.
      $open_groups = social_group_get_all_open_groups();
      $group_memberships = \Drupal::service('social_group.helper_service')
        ->getAllGroupsForUser($account->id());
    }
    $groups = array_merge($open_groups, $group_memberships);
    $groups_unique = array_unique($groups);

    // Add tables and joins.
    $this->query->addTable('activity__field_activity_recipient_group');
    $this->query->addTable('activity__field_activity_entity');
    $this->query->addTable('activity__field_activity_recipient_user');

    $configuration = [
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'post_field_data',
      'field' => 'id',
      'operator' => '=',
      'extra' => [
        0 => [
          'left_field' => 'field_activity_entity_target_type',
          'value' => 'post',
        ],
      ],
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('post', $join, 'activity__field_activity_entity');

    $configuration = [
      'left_table' => 'post',
      'left_field' => 'id',
      'table' => 'post__field_visibility',
      'field' => 'entity_id',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('post__field_visibility', $join, 'post__field_visibility');

    // Join node table(s).
    $configuration = [
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'node_access',
      'field' => 'nid',
      'operator' => '=',
      'extra' => [
        0 => [
          'left_field' => 'field_activity_entity_target_type',
          'value' => 'node',
        ],
      ],
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node_access', $join, 'node_access_relationship');

    if ($account->isAnonymous()) {
      $configuration['table'] = 'node_field_data';
      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('node_field_data', $join, 'node_field_data');
    }

    // Add queries.
    $and_wrapper = new Condition('AND');
    $or = new Condition('OR');

    // Allow us to check for authenticated users.
    $authenticated = $account->isAuthenticated();

    // Nodes: retrieve all the nodes 'created' activity by node access grants.
    $node_access = new Condition('AND');
    $node_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'node', '=');
    $node_access_grants = node_access_grants('view', $account);
    $grants = new Condition('OR');
    foreach ($node_access_grants as $realm => $gids) {
      if (!empty($gids)) {
        $and = new Condition('AND');

        if ($account->isAnonymous() && strpos($realm, 'field_content_visibility_community') !== FALSE) {
          $and->condition('node_field_data.uid', 0, '!=');
        }

        $grants->condition($and
          ->condition('node_access.gid', $gids, 'IN')
          ->condition('node_access.realm', $realm)
        );
      }
    }
    $node_access->condition($grants);
    $or->condition($node_access);

    // Posts: retrieve all the posts in groups the user is a member of.
    if ($authenticated && count($groups_unique) > 0) {
      $posts_in_groups = new Condition('AND');
      $posts_in_groups->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post', '=');
      $posts_in_groups->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $groups_unique, 'IN');

      $or->condition($posts_in_groups);
    }

    // Posts: all the posts the user has access to by permission.
    $post_access = new Condition('AND');
    $post_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post', '=');
    $post_access->condition('post__field_visibility.field_visibility_value', '3', '!=');

    if (!$account->hasPermission('view public posts')) {
      $post_access->condition('post__field_visibility.field_visibility_value', '1', '!=');
    }
    if (!$account->hasPermission('view community posts')) {
      $post_access->condition('post__field_visibility.field_visibility_value', '2', '!=');
      // Also do not show recipient posts (e.g. on open groups).
      $post_access->condition('post__field_visibility.field_visibility_value', '0', '!=');
    }

    $or->condition($post_access);

    $post_status = new Condition('OR');
    $post_status->condition('post.status', 1, '=');

    if ($account->hasPermission('view unpublished post entities')) {
      $post_status->condition('post.status', 0, '=');
    }
    $post_status->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post', '!=');
    $and_wrapper->condition($post_status);

    // Comments: retrieve comments the user has access to.
    if ($account->hasPermission('access comments')) {
      // For comments in groups, the user must be a member of at least 1 group.
      if (count($groups_unique) > 0) {
        $comments_on_content_in_groups = new Condition('AND');
        $comments_on_content_in_groups->condition('activity__field_activity_entity.field_activity_entity_target_type', 'comment', '=');
        $comments_on_content_in_groups->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $groups_unique, 'IN');
        $or->condition($comments_on_content_in_groups);
      }

      $comments_on_content = new Condition('AND');
      $comments_on_content->condition('activity__field_activity_entity.field_activity_entity_target_type', 'comment', '=');
      $comments_on_content->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id');
      $or->condition($comments_on_content);
    }

    // For likes, mentions, private messages and background tasks we can safely
    // assume these end up only in the recipient user. The context takes care
    // of only sending notifications if they have actual access.
    if ($authenticated) {
      $vote_access = new Condition('AND');
      $vote_access->condition('activity__field_activity_entity.field_activity_entity_target_type', [
        'vote',
        'mentions',
        'private_message',
        'queue_storage_entity',
      ], 'IN');
      $vote_access->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', (string) $account->id());
      $or->condition($vote_access);
    }

    // For an enrollment we match the recipient uid for enrollments
    // activities are only created for those with the OrganizerActivityContext
    // see getRecipientOrganizerFromEntity.
    if ($authenticated) {
      $enrollment_access = new Condition('AND');
      $enrollment_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'event_enrollment');
      $enrollment_access->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', (string) $account->id());
      $or->condition($enrollment_access);
    }

    // For group_content
    // we match the field_activity_recipient_user_target_id for
    // GroupContentInMyGroupActivityContext &
    // ContentInMyGroupActivityContext to ensure only users who are GM
    // or actual members get the message.
    // Or.
    // We match field_activity_recipient_group_target_id
    // see GroupActivityContext so we can match our own memberships against it.
    if ($authenticated) {
      $membership_access = new Condition('AND');
      $membership_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'group_content');
      $membership_or_node = new Condition('OR');
      $membership_or_node->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', (string) $account->id());
      if (count($groups_unique) > 0) {
        $membership_or_node->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $groups_unique, 'IN');
      }
      $membership_access->condition($membership_or_node);
      $or->condition($membership_access);
    }

    // Lets add all the or conditions to the Views query.
    $and_wrapper->condition($or);
    $this->query->addWhere('visibility', $and_wrapper);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user.permissions';
    $contexts[] = 'route.group';

    return $contexts;
  }

}
