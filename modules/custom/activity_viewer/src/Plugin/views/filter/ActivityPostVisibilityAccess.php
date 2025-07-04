<?php

namespace Drupal\activity_viewer\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\social_node\QueryAccess\NodeEntityQueryAlter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters activity based on visibility settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_post_visibility_access")
 */
class ActivityPostVisibilityAccess extends FilterPluginBase {

  /**
   * The group helper.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelper;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SocialGroupHelperService $group_helper,
    RouteMatchInterface $route_match,
    protected ClassResolverInterface $classResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->groupHelper = $group_helper;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('social_group.helper_service'),
      $container->get('current_route_match'),
      $container->get('class_resolver')
    );
  }

  /**
   * Not exposable.
   */
  public function canExpose(): bool {
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
  public function query():void {
    $account = $this->view->getUser();

    if ($this->moduleHandler->moduleExists('social_group')) {
      // @todo This creates a dependency on Social Group which shouldn't exist,
      // this access logic should be in that module instead.
      $group_memberships = $this->groupHelper->getAllGroupsForUser($account->id());
    }

    $groups = [
      ...$group_memberships ?? [],
    ];

    $groups_unique = array_unique($groups);

    // Add tables and joins.
    $this->query->addTable('activity__field_activity_recipient_group');
    $this->query->addTable('activity__field_activity_entity');

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
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->query->addRelationship('post', $join, 'activity__field_activity_entity');

    $configuration = [
      'left_table' => 'post',
      'left_field' => 'id',
      'table' => 'post__field_visibility',
      'field' => 'entity_id',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->query->addRelationship('post__field_visibility', $join, 'post__field_visibility');

    // Join group content table.
    $configuration = [
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'group_relationship_field_data',
      'field' => 'id',
      'operator' => '=',
      'extra' => [
        0 => [
          'left_field' => 'field_activity_entity_target_type',
          'value' => 'group_content',
        ],
      ],
    ];
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $this->query->addRelationship('group_content', $join, 'group_content');

    // Add queries.
    $and_wrapper = new Condition('AND');
    $or = new Condition('OR');

    $node_access = $this->query->getConnection()->select('node_field_data', 'node_field_data');
    $node_access->addField('afae', 'entity_id');
    $node_access->join('activity__field_activity_entity', 'afae', 'node_field_data.nid = afae.field_activity_entity_target_id');
    $node_access->condition('afae.field_activity_entity_target_type', 'node');
    if (!$account->hasPermission('bypass node access')) {
      $node_access->condition('node_field_data.status', '1');
    }

    // Alter a query with node access rules.
    if (class_exists(NodeEntityQueryAlter::class)) {
      $this->classResolver
        ->getInstanceFromDefinition(NodeEntityQueryAlter::class)
        ->alterQuery($node_access);
    }

    $or->condition('activity_field_data.id', $node_access, 'IN');

    // Posts: retrieve all the posts in groups the user is a member of.
    if ($account->isAuthenticated() && count($groups_unique) > 0) {
      $posts_in_groups = new Condition('AND');
      $posts_in_groups->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post');
      $posts_in_groups->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $groups_unique, 'IN');

      $or->condition($posts_in_groups);
    }

    // Posts: all the posts the user has access to by permission.
    $post_access = new Condition('AND');
    $post_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post');

    // Get group from url-parameter.
    $group = $this->routeMatch->getParameter('group');
    // If the group parameter isn't group entity, visibility rules.
    // And check group permission when group parameter is group entity.
    if (!$group instanceof GroupInterface || !$group->hasPermission('access content overview', $account)) {
      $post_access->condition('post__field_visibility.field_visibility_value', '3', '!=');

      if (!$account->hasPermission('view public posts')) {
        $post_access->condition('post__field_visibility.field_visibility_value', '1', '!=');
      }
      if (!$account->hasPermission('view community posts')) {
        $post_access->condition('post__field_visibility.field_visibility_value', '2', '!=');
        // Also do not show recipient posts (e.g. on open groups).
        $post_access->condition('post__field_visibility.field_visibility_value', '0', '!=');
      }
    }

    $or->condition($post_access);

    $post_status = new Condition('OR');
    $post_status->condition('post.status', 1);

    if ($account->hasPermission('view unpublished post entities')) {
      $post_status->condition('post.status', 0);
    }
    $post_status->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post', '!=');
    $and_wrapper->condition($post_status);

    // Comments: retrieve comments the user has access to.
    if ($account->hasPermission('access comments')) {
      // For comments in groups, the user must be a member of at least 1 group.
      if (count($groups_unique) > 0) {
        $comments_on_content_in_groups = new Condition('AND');
        $comments_on_content_in_groups->condition('activity__field_activity_entity.field_activity_entity_target_type', 'comment');
        $comments_on_content_in_groups->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $groups_unique, 'IN');
        $or->condition($comments_on_content_in_groups);
      }

      $comments_on_content = new Condition('AND');
      $comments_on_content->condition('activity__field_activity_entity.field_activity_entity_target_type', 'comment');
      $comments_on_content->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id');
      $or->condition($comments_on_content);
    }

    // For "group content" entities we need to add the condition
    // to check what groups user has access.
    $membership_access = new Condition('AND');
    $membership_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'group_content');
    $membership_access->condition('group_content.gid', $groups_unique ?: [0], 'IN');
    $or->condition($membership_access);

    // Lets add all the or conditions to the Views query.
    $and_wrapper->condition($or);
    $this->query->addWhere('visibility', $and_wrapper);
  }

}
