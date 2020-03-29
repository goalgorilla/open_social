<?php

namespace Drupal\social_activity_filter\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filters activity by the taxonomy tags in the stream block.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_filter_tags")
 */
class ActivityFilterTags extends FilterPluginBase {

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
    $tags = $this->view->filter_tags;
    $taxonomy_field = $this->view->filter_vocabulary;
    $taxonomy_table = "node__{$taxonomy_field}";

    // @todo: replace it!
    $database = \Drupal::database();
    if($database->schema()->tableExists($taxonomy_table)) {

      $this->query->addTable($taxonomy_table);

      $configuration = [
        'left_table' => 'activity__field_activity_entity',
        'left_field' => 'field_activity_entity_target_id',
        'table' => $taxonomy_table,
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
      $this->query->addRelationship($taxonomy_field, $join, $taxonomy_table);

      $and_wrapper = db_and();
      $and_wrapper->condition("{$taxonomy_field}.{$taxonomy_field}_target_id", $tags, 'IN');

      // Lets add all the or conditions to the Views query.
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
