<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\Core\Cache\Cache;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters content by the groups it is published in.
 *
 * Cross-posted content is related to more than one group, so the groups are
 * matched with a sub-query instead of a relationship, which would multiply the
 * rows of a view.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_group_node_groups")
 */
class NodeGroups extends FilterByGroup {

  /**
   * The group relation type manager.
   */
  protected GroupRelationTypeManagerInterface $groupRelationTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->groupRelationTypeManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * The matched content changes when content is (cross-)posted to another
   * group, the group names to filter on change when a group is renamed.
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['group_list', 'group_content_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Only group IDs can be matched, anything else (the "All" placeholder of
    // an exposed filter, an empty selection) means no filtering.
    $group_ids = array_filter((array) $this->value, 'is_numeric');
    $plugin_ids = $this->groupRelationTypeManager->getPluginIdsByEntityTypeId('node');
    if ($group_ids === [] || $plugin_ids === []) {
      return;
    }

    $this->ensureMyTable();

    $relationships = $this->database->select('group_relationship_field_data', 'grfd');
    $relationships->addField('grfd', 'entity_id');
    $relationships->condition('grfd.plugin_id', $plugin_ids, 'IN');
    $relationships->condition('grfd.gid', array_values($group_ids), 'IN');

    $query = $this->query;
    assert($query instanceof Sql);
    $query->addWhere(
      $this->options['group'],
      "$this->tableAlias.$this->realField",
      $relationships,
      $this->operator === 'not in' ? 'NOT IN' : 'IN',
    );
  }

}
