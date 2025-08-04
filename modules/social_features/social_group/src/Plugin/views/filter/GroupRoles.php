<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler for group membership roles.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("group_roles")]
class GroupRoles extends ManyToOne {

  /**
   * Constructs a GroupRoles object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // Allow other modules to exclude specific group roles.
    $excluded_group_ids = $this->getModuleHandler()->invokeAll('exclude_group_roles_on_admin_people');

    $group_role_ids = $this->entityTypeManager
      ->getStorage('group_role')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('group_type', $excluded_group_ids ?: [0], 'NOT IN')
      ->execute();

    if (empty($group_role_ids)) {
      return $this->valueOptions;
    }

    /** @var \Drupal\group\Entity\GroupRoleInterface[] $group_roles */
    $group_roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadMultiple($group_role_ids);

    // Allow other modules to exclude specific group roles.
    $excluded_role_ids = $this->getModuleHandler()->invokeAll('exclude_group_roles_on_admin_people');

    // For the moment we need only "member" and "group manager" roles.
    $target_group_roles = array_filter(
      array: $group_roles,
      callback: fn(GroupRoleInterface $role) => !in_array($role->id(), $excluded_role_ids) &&
        str_ends_with((string) $role->id(), '-member') ||
        str_ends_with((string) $role->id(), '-group_manager')
    );

    // Build options list.
    foreach ($target_group_roles as $role_id => $role) {
      $group_type = $role->getGroupType();

      // Exception for a "Flexible Group" membership role.
      if ($group_type->id() === 'flexible_group' && str_ends_with((string) $role->id(), '-member')) {
        $target_group_roles[$role_id] = $this->t('Group') . ' ' . strtolower((string) $role->label());
        continue;
      }

      $target_group_roles[$role_id] = str_ends_with((string) $role->id(), '-member')
        // Label for a "member" role.
        ? (string) $group_type->label() . ' ' . strtolower((string) $role->label())
        // Label for a "group manager" role.
        : ucfirst(strtolower((string) $role->label()));
    }

    $this->valueOptions = $target_group_roles;

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function query(): void {
    // Get the user input value.
    $values = (array) $this->value;

    if (empty($values)) {
      return;
    }

    foreach ($values as $value) {
      if (str_ends_with($value, '-member')) {
        $group_membership_roles[] = $value;
      }

      if (str_ends_with($value, '-group_manager')) {
        $group_manager_roles[] = $value;
      }
    }

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    // Make sure we have access to the group relationship table.
    $group_relationship_join_configuration = [
      'table' => 'group_relationship_field_data',
      'field' => 'entity_id',
      'left_table' => 'users_field_data',
      'left_field' => 'uid',
      'type' => 'LEFT',
    ];

    $join = Views::pluginManager('join')
      ->createInstance('standard', $group_relationship_join_configuration);
    assert($join instanceof JoinPluginBase);

    $group_relationship_table = $query->addRelationship('grfd', $join, 'group_relationship_field_data');

    // We can't filter users just by role ids as roles are configuration
    // entities.
    // Based on group roles, we need to get the "group_membership" group
    // relation ids and then build a condition.
    if (!empty($group_membership_roles)) {
      $group_roles = $this->entityTypeManager
        ->getStorage('group_role')
        ->loadMultiple($group_membership_roles);

      /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $group_content_type_storage */
      $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
      foreach ($group_roles as $group_role) {
        assert($group_role instanceof GroupRoleInterface);
        $group_type = $group_role->getGroupType();
        $group_membership_ids[] = $group_content_type_storage
          ->getRelationshipTypeId((string) $group_type->id(), 'group_membership');
      }
    }

    // If CM+ chose "member" and "group manager" role in a filter,
    // then we need to add a new group condition with "OR" conjunction,
    // otherwise we will get only "group managers",
    // because of a "group_roles" table left join.
    if (isset($group_membership_ids, $group_manager_roles)) {
      $query->setWhereGroup('OR', $or_group = count($query->where) + 1);
    }

    // Add condition to the query if any "member" role was chosen.
    if (!empty($group_membership_ids)) {
      $query->addWhere(
        $or_group ?? $this->options['group'],
        "$group_relationship_table.type",
        $group_membership_ids,
        'IN'
      );
    }

    // For "group managers" we have a separate table which we need to join.
    // Add condition to the query if any "group manager" role was chosen.
    if (!empty($group_manager_roles)) {
      $join_configuration = [
        'table' => 'group_content__group_roles',
        'field' => 'entity_id',
        'left_table' => $group_relationship_table,
        'left_field' => 'id',
        'type' => 'LEFT',
      ];

      $join = Views::pluginManager('join')
        ->createInstance('standard', $join_configuration);
      assert($join instanceof JoinPluginBase);

      $alias = $query->addRelationship('group_roles', $join, 'group_content__group_roles');

      $query->addWhere(
        $or_group ?? $this->options['group'],
        "$alias.group_roles_target_id",
        $group_manager_roles,
        'IN'
      );
    }
  }

}
