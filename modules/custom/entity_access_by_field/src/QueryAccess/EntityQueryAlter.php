<?php

namespace Drupal\entity_access_by_field\QueryAccess;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\Tables;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface as CGPII;
use Drupal\group\Access\ChainGroupPermissionCalculatorInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a class for altering entity queries.
 *
 * EntityQuery doesn't have an alter hook, forcing this class to operate
 * on the underlying SQL query, duplicating the EntityQuery condition logic.
 *
 * @internal
 */
class EntityQueryAlter implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The query cacheable metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheableMetadata;

  /**
   * The data table alias.
   *
   * @var string|false
   */
  protected $dataTableAlias = FALSE;

  /**
   * Constructs a new EntityQueryAlter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   * @param \Drupal\group\Access\ChainGroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupContentEnablerManagerInterface $plugin_manager, ChainGroupPermissionCalculatorInterface $permission_calculator, Connection $database, RendererInterface $renderer, RequestStack $request_stack, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->permissionCalculator = $permission_calculator;
    $this->database = $database;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->cacheableMetadata = new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('group_permission.chain_calculator'),
      $container->get('database'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * Alters the select query for the given entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function alter(SelectInterface $query, EntityTypeInterface $entity_type) {
    $this->doAlter($query, $entity_type, $query->getMetaData('op') ?: 'view');
    $this->applyCacheability();
  }

  /**
   * Actually alters the select query for the given entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $operation
   *   The query operation.
   */
  protected function doAlter(SelectInterface $query, EntityTypeInterface $entity_type, $operation) {
    $entity_type_id = $entity_type->id();
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }

    // Find all of the group content plugins that define access.
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess($entity_type_id);
    if (empty($plugin_ids)) {
      return;
    }

    $account = $this->currentUser;
    $this->cacheableMetadata->addCacheContexts(['user.permissions']);
    if ($account->hasPermission('bypass group access') || $account->hasPermission('administer nodes')) {
      return;
    }

    $group_access = NULL;
    $base_table = $entity_type->getBaseTable();
    $id_key = $entity_type->getKey('id');

    $query->join(
      'node__field_content_visibility',
      'fcv',
      "node_field_data.nid=fcv.entity_id"
    );

    // Add extra condition for Group Membership
    // related check in Flexible groups.
    $group_memberships = \Drupal::service('social_group.helper_service')->getAllGroupsForUser($account->id());
    if (!empty($group_memberships) && !$account->isAnonymous()) {
      $query->join(
        'group_content_field_data',
        'gcfd',
        "node_field_data.nid=gcfd.entity_id"
      );

      $query->condition(
         $query->orConditionGroup()
           ->isNull('gcfd.entity_id')
           ->condition('field_content_visibility_value', 'group', '!=')
           ->condition($group_condition = $query->andConditionGroup())
      );

      $group_condition->condition('gcfd.gid', $group_memberships, 'IN');
      $group_condition->condition('field_content_visibility_value', 'group');
    }

    $group_visible = $query->orConditionGroup();
    $group_visible->condition('field_content_visibility_value', 'public');
    $group_visible->condition('field_content_visibility_value', 'group', '!=');

    if (!$account->isAnonymous()) {
      $group_visible->condition('field_content_visibility_value', 'community');
    }

    // And we should check for open / public.
//    $query->condition($group_visible);
    // From this point on, we know there is something that will allow access, so
    // we need to alter the query to check that entity_id is null or the group
    // access checks apply.
    // Since nodes can live in multiple groups, make it distinct.
    $query->distinct(TRUE);
  }

  /**
   * Applies the cacheablity metadata to the current request.
   */
  protected function applyCacheability() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->isMethodCacheable() && $this->renderer->hasRenderContext() && $this->hasCacheableMetadata()) {
      $build = [];
      $this->cacheableMetadata->applyTo($build);
      $this->renderer->render($build);
    }
  }

  /**
   * Check if the cacheable metadata is not empty.
   *
   * An empty cacheable metadata object has no context, tags, and is permanent.
   *
   * @return bool
   *   TRUE if there is cacheability metadata, otherwise FALSE.
   */
  protected function hasCacheableMetadata() {
    return $this->cacheableMetadata->getCacheMaxAge() !== Cache::PERMANENT
      || count($this->cacheableMetadata->getCacheContexts()) > 0
      || count($this->cacheableMetadata->getCacheTags()) > 0;
  }

}
