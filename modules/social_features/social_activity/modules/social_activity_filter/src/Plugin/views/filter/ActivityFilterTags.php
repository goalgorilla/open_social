<?php

namespace Drupal\social_activity_filter\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters activity by the taxonomy tags in the stream block.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_filter_tags")
 */
class ActivityFilterTags extends FilterPluginBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * Not exposable.
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Filters out activity items by the taxonomy tags.
   */
  public function query() {
    $tags = isset($this->view->filter_tags) ? $this->view->filter_tags : '';
    $taxonomy_field = isset($this->view->filter_vocabulary) ? $this->view->filter_vocabulary : '';

    $or = new Condition('OR');
    $and_wrapper = new Condition('AND');

    $taxonomy_node_table = "node__{$taxonomy_field}";
    $taxonomy_post_table = "post__field_{$taxonomy_field}";
    $activity_entity_table = 'activity__field_activity_entity';

    // Filter Nodes by selected tags.
    if ($this->database->schema()->tableExists($taxonomy_node_table)) {
      $this->query->addTable($taxonomy_node_table);
      $this->query->addTable($activity_entity_table);

      $configuration = [
        'left_table' => $activity_entity_table,
        'left_field' => 'field_activity_entity_target_id',
        'table' => $taxonomy_node_table,
        'field' => 'entity_id',
        'operator' => '=',
        'extra' => [
          0 => [
            'left_field' => 'field_activity_entity_target_type',
            'value' => 'node',
          ],
        ],
      ];
      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship('filtered_nodes', $join, $taxonomy_node_table);

      $and_node_wrapper = new Condition('AND');
      $and_node_wrapper->condition("filtered_nodes.{$taxonomy_field}_target_id", $tags, 'IN');

      $or->condition($and_node_wrapper);

      // Attach commented entity activity.
      $comment_table = 'comment_field_data';
      $this->query->addTable($comment_table);

      $configuration = [
        'left_table' => $activity_entity_table,
        'left_field' => 'field_activity_entity_target_id',
        'table' => $comment_table,
        'field' => 'cid',
        'operator' => '=',
        'extra' => [
          0 => [
            'left_field' => 'field_activity_entity_target_type',
            'value' => 'comment',
          ],
        ],
      ];
      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship($comment_table, $join, $comment_table);

      $and_comment_wrapper = new Condition('AND');
      $and_comment_wrapper->condition("{$comment_table}.comment_type", 'comment');

      $configuration = [
        'left_table' => $comment_table,
        'left_field' => 'entity_id',
        'table' => $taxonomy_node_table,
        'field' => 'entity_id',
        'operator' => '=',
      ];

      // Apply filter by tags to commented entity activity.
      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship('commented_nodes', $join, $comment_table);
      $and_comment_wrapper->condition("commented_nodes.{$taxonomy_field}_target_id", $tags, 'IN');

      $or->condition($and_comment_wrapper);
    }

    // Filter Posts by selected tags.
    if ($this->database->schema()->tableExists($taxonomy_post_table)) {
      $configuration = [
        'table' => $taxonomy_post_table,
        'field' => 'entity_id',
        'left_table' => $activity_entity_table,
        'left_field' => 'field_activity_entity_target_id',
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
      $this->query->addRelationship('filtered_posts', $join, $taxonomy_post_table);

      $and_post_wrapper = new Condition('AND');
      $and_post_wrapper->condition("filtered_posts.field_{$taxonomy_field}_target_id", $tags, 'IN');
      $or->condition($and_post_wrapper);
    }

    // Lets add all the or conditions to the Views query.
    if ($or->count()) {
      $and_wrapper->condition($or);
      $this->query->addWhere('tags', $and_wrapper);
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

}
