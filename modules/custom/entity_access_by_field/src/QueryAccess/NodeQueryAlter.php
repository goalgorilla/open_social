<?php

namespace Drupal\entity_access_by_field\QueryAccess;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\QueryAccess\EntityQueryAlter;

/**
 * Defines a class for altering entity queries.
 *
 * @todo Revisit cacheability and see if we can optimize some more.
 *
 * @internal
 */
class NodeQueryAlter extends EntityQueryAlter {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The alias for the join to the plugins.
   *
   * @var string
   */
  protected $joinAliasPlugins;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('group_relation_type.manager');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doAlter($operation) {
    $entity_type_id = $this->entityType->id();
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }

    // Find all of the group relations that define access.
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess($entity_type_id);
    if (empty($plugin_ids)) {
      return;
    }

    // If any new relationship entity is added using any of the retrieved
    // plugins, it might change access.
    $cache_tags = [];
    foreach ($plugin_ids as $plugin_id) {
      $cache_tags[] = "group_content_list:plugin:$plugin_id";
    }
    $this->cacheableMetadata->addCacheTags($cache_tags);

    // If there are no relationships using the plugins, there's no point in going
    // any further. The cache tags above will invalidate our result if new group
    // content is created using the plugins that define access. Retrieve the
    // plugin IDs in use to optimize a loop further below.
    $group_relationship_data_table = $this->entityTypeManager->getDefinition('group_content')->getDataTable();
    $plugin_ids_in_use = $this->database
      ->select($group_relationship_data_table, 'gc')
      ->fields('gc', ['plugin_id'])
      ->condition('plugin_id', $plugin_ids, 'IN')
      ->distinct()
      ->execute()
      ->fetchCol();

    if (empty($plugin_ids_in_use)) {
      return;
    }

    // Check if any of the plugins actually support the operation. If not, we
    // can simply bail out here to play nice with other modules that do support
    // the provided operation.
    $operation_is_supported = FALSE;
    foreach ($plugin_ids_in_use as $plugin_id) {
      if ($this->pluginManager->getAccessControlHandler($plugin_id)->supportsOperation($operation, 'entity')) {
        $operation_is_supported = TRUE;
        break;
      }
    }

    if (!$operation_is_supported) {
      return;
    }

    // From this point onward, we know that there are grouped entities and that
    // we need to check access, so we can LEFT JOIN the necessary table.
    $id_key = $this->entityType->getKey('id');

    // Join the relationship table, but only for used plugins.
    $base_table = $this->ensureBaseTable();
    $this->joinAliasPlugins = $this->query->leftJoin(
      $group_relationship_data_table,
      'gcfd',
      "$base_table.$id_key=%alias.entity_id AND %alias.plugin_id IN (:plugin_ids_in_use[])",
      [':plugin_ids_in_use[]' => $plugin_ids_in_use]
    );

    // Retrieve the full list of group permissions for the user.
    $this->cacheableMetadata->addCacheContexts(['user.group_permissions']);
    $calculated_permissions = $this->permissionCalculator->calculateFullPermissions($this->currentUser);

    // We only check unpublished vs published for "view" right now. If we ever
    // start supporting other operations, we need to remove the "view" check.
    $check_published = $operation === 'view' && $this->entityType->entityClassImplements(EntityPublishedInterface::class);

    $owner_key = $this->entityType->getKey('owner');
    $published_key = $this->entityType->getKey('published');

    $allowed_any_ids = $allowed_own_ids = $allowed_any_by_status_ids = $allowed_own_by_status_ids = [];
    foreach ($plugin_ids_in_use as $plugin_id) {
      $handler = $this->pluginManager->getPermissionProvider($plugin_id);
      $admin_permission = $handler->getAdminPermission();
      $any_permission = $handler->getPermission($operation, 'entity', 'any');
      $own_permission = $handler->getPermission($operation, 'entity', 'own');
      if ($check_published) {
        $any_unpublished_permission = $handler->getPermission("$operation unpublished", 'entity', 'any');
        $own_unpublished_permission = $handler->getPermission("$operation unpublished", 'entity', 'own');
      }

      foreach ($calculated_permissions->getItems() as $item) {
        if ($admin_permission !== FALSE && $item->hasPermission($admin_permission)) {
          $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
        elseif(!$check_published) {
          if ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
            $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
          elseif($own_permission !== FALSE && $item->hasPermission($own_permission)) {
            $allowed_own_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
        }
        else {
          if ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
            $allowed_any_by_status_ids[1][$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
          elseif($own_permission !== FALSE && $item->hasPermission($own_permission)) {
            $allowed_own_by_status_ids[1][$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
          if ($any_unpublished_permission !== FALSE && $item->hasPermission($any_unpublished_permission)) {
            $allowed_any_by_status_ids[0][$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
          elseif($own_unpublished_permission !== FALSE && $item->hasPermission($own_unpublished_permission)) {
            $allowed_own_by_status_ids[0][$item->getScope()][$plugin_id][] = $item->getIdentifier();
          }
        }
      }
    }

    $has_regular_ids = !empty($allowed_any_ids);
    $has_own_ids = !empty($allowed_own_ids);
    $has_status_ids = FALSE;

    $statuses_to_check = [];
    foreach ([0, 1] as $status) {
      if (!empty($allowed_any_by_status_ids[$status]) || !empty($allowed_own_by_status_ids[$status])) {
        $has_status_ids = TRUE;
        $statuses_to_check[] = $status;
      }
    }

    // If no group type or group gave access, we deny access altogether.
    if (!$has_regular_ids && !$has_own_ids && !$has_status_ids) {
      $this->query->isNull("$this->joinAliasPlugins.entity_id");
      return;
    }

    // From this point on, we know there is something that will allow access, so
    // we need to alter the query to check that entity_id is null or the group
    // access checks apply.
    $this->query->condition(
      $group_conditions = $this->query->orConditionGroup()
        ->isNull("$this->joinAliasPlugins.entity_id")
    );

    // If we're going to need a data table anyhow, might as well initialize it
    // early so all checks are added to the same table.
    if ($has_own_ids || $has_status_ids) {
      $data_table = $this->ensureDataTable();
    }

    if ($has_regular_ids) {
      $this->addScopedConditions($allowed_any_ids, $group_conditions);
    }

    if ($has_own_ids) {
      $this->cacheableMetadata->addCacheContexts(['user']);
      $group_conditions->condition($owner_conditions = $this->query->andConditionGroup());
      $owner_conditions->condition("$data_table.$owner_key", $this->currentUser->id());
      $this->addScopedConditions($allowed_own_ids, $owner_conditions);
    }

    if ($has_status_ids) {
      foreach ($statuses_to_check as $status) {
        $group_conditions->condition($status_conditions = $this->query->andConditionGroup());
        $status_conditions->condition("$data_table.$published_key", $status);
        $status_conditions->condition($status_sub_conditions = $this->query->orConditionGroup());

        if (!empty($allowed_any_by_status_ids[$status])) {
          $this->addScopedConditions($allowed_any_by_status_ids[$status], $status_sub_conditions);
        }

        if (!empty($allowed_own_by_status_ids[$status])) {
          $this->cacheableMetadata->addCacheContexts(['user']);
          $status_sub_conditions->condition($status_owner_conditions = $this->query->andConditionGroup());
          $status_owner_conditions->condition("$data_table.$owner_key", $this->currentUser->id());
          $this->addScopedConditions($allowed_own_by_status_ids[$status], $status_owner_conditions);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginDataTable() {
    return $this->joinAliasPlugins;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return $this->joinAliasPlugins;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'gid';
  }

}
