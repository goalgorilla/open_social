<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\views\field;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present all groups content is published in.
 *
 * Content can be cross-posted, so it can be related to more than one group.
 * Joining the group relationship table would multiply the rows of the view,
 * therefore the groups are looked up for the whole result set at once.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_group_node_groups")
 */
class NodeGroups extends FieldPluginBase implements CacheableDependencyInterface {

  /**
   * The groups of the nodes in the result set, keyed by node ID.
   *
   * @var array<int, \Drupal\group\Entity\GroupInterface[]>
   */
  protected array $groups = [];

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GroupRelationTypeManagerInterface $groupRelationTypeManager,
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
      $container->get('group_relation_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * The groups are not part of the query, they are looked up in preRender().
   */
  public function query(): void {}

  /**
   * {@inheritdoc}
   */
  public function clickSortable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * A column with several groups has no single value to sort on, so it is
   * sorted on the group the content was published in first.
   */
  public function clickSort($order): void {
    $plugin_ids = $this->groupRelationTypeManager->getPluginIdsByEntityTypeId('node');
    if ($plugin_ids === []) {
      return;
    }

    $this->ensureMyTable();

    // The placeholder is named after the handler, the query builder would give
    // the sub-query placeholders that clash with those of the view's query.
    $placeholder = ':social_group_node_groups_plugin_ids[]';
    $original_group = "(SELECT gfd.label FROM {groups_field_data} gfd" .
      " INNER JOIN {group_relationship_field_data} grfd ON grfd.gid = gfd.id" .
      " WHERE grfd.entity_id = $this->tableAlias.$this->realField" .
      " AND gfd.default_langcode = 1 AND grfd.plugin_id IN ($placeholder)" .
      " ORDER BY grfd.created, grfd.id LIMIT 1)";

    $query = $this->query;
    assert($query instanceof Sql);
    $query->addOrderBy(
      NULL,
      $original_group,
      $order,
      'social_group_node_groups',
      ['placeholders' => [$placeholder => $plugin_ids]],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * The groups are rendered outside of the render context, so the column has
   * to tell the view what invalidates it: renaming a group, and posting or
   * cross-posting content to another group.
   */
  public function getCacheTags(): array {
    return ['group_list', 'group_content_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values): void {
    $this->groups = [];

    $node_ids = [];
    foreach ($values as $row) {
      $node = $this->getEntity($row);
      if ($node instanceof NodeInterface) {
        $node_ids[] = (int) $node->id();
      }
    }

    $plugin_ids = $this->groupRelationTypeManager->getPluginIdsByEntityTypeId('node');
    if ($node_ids === [] || $plugin_ids === []) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('group_content');
    $relationship_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $node_ids, 'IN')
      ->condition('plugin_id', $plugin_ids, 'IN')
      // The group the content was published in first comes first, the groups
      // it was cross-posted to follow in the order they were added.
      ->sort('created')
      ->sort('id')
      ->execute();

    // Loading the relationships loses the order of the query, so walk over the
    // sorted IDs instead of over the loaded relationships.
    $relationships = $storage->loadMultiple($relationship_ids);

    $group_ids = [];
    foreach ($relationship_ids as $relationship_id) {
      $relationship = $relationships[$relationship_id] ?? NULL;
      if ($relationship !== NULL) {
        $group_ids[(int) $relationship->getEntityId()][] = $relationship->getGroupId();
      }
    }

    $groups = $this->entityTypeManager->getStorage('group')
      ->loadMultiple(array_unique(array_merge(...array_values($group_ids))));

    foreach ($group_ids as $node_id => $node_group_ids) {
      foreach ($node_group_ids as $group_id) {
        if (isset($groups[$group_id])) {
          $this->groups[$node_id][$group_id] = $groups[$group_id];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $this->getEntity($values);
    if (!$node instanceof NodeInterface) {
      return '';
    }

    $items = [];
    foreach ($this->groups[(int) $node->id()] ?? [] as $group) {
      $items[] = [
        '#type' => 'link',
        '#title' => $group->label(),
        '#url' => $group->toUrl(),
      ];
    }

    if ($items === []) {
      return '';
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return ViewsRenderPipelineMarkup::create(
      (string) $this->getRenderer()->renderInIsolation($build)
    );
  }

}
