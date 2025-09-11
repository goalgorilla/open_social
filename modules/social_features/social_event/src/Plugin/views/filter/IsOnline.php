<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Views;

/**
 * Filters events based on whether they have an online meeting configured.
 *
 * This filter checks if an event has an associated meeting (online event)
 * by examining the field_event_meeting field. It only applies to views with
 * 'node_field_data' as the base table.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("social_event_is_online")]
final class IsOnline extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  public function init($view, $display, ?array &$options = NULL): void {
    parent::init($view, $display, $options);

    // Set the default value to false (show all events by default).
    if (!isset($this->value)) {
      $this->value = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Only filter if the checkbox is checked.
    if (!$this->value) {
      // If a checkbox is unchecked (default),
      // show all events - no filtering applied.
      return;
    }

    $query = $this->query;
    assert($query instanceof Sql);

    $this->ensureMyTable();

    // Get base table, this filter is for event nodes.
    $base_table = $this->relationship ?: $this->view->storage->get('base_table');

    // Join the "node__field_event_meeting" table.
    $field_table = 'node__field_event_meeting';
    $configuration = [
      'table' => $field_table,
      'field' => 'entity_id',
      'left_table' => $base_table,
      'left_field' => 'nid',
      'type' => 'LEFT',
    ];

    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $alias = $query->addRelationship($field_table, $join, $base_table);

    // Show only online events (events with meeting configured).
    $query->addWhere($this->options['group'], "$alias.field_event_meeting_target_id", NULL, 'IS NOT NULL');
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): TranslatableMarkup {
    if (empty($this->value)) {
      return $this->t('Show all events (default)');
    }

    return $this->t('Show only online events');
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    parent::valueForm($form, $form_state);

    // The widget should behave like a checkbox.
    $form['value']['#type'] = 'checkbox';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache_contexts = parent::getCacheContexts();

    // Add cache context for the filter value.
    if (!in_array('url.query_args', $cache_contexts)) {
      $cache_contexts[] = 'url.query_args';
    }

    return $cache_contexts;
  }

}
