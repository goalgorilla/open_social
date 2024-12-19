<?php

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filters events based on created or enrolled status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("event_enrolled_or_created")
 */
class EventEnrolledOrCreated extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): FALSE {
    return FALSE;
  }

  /**
   * Query for the activity stream on the account pages.
   */
  public function query(): void {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    $account_profile = \Drupal::routeMatch()->getParameter('user');
    if ($account_profile instanceof UserInterface) {
      $account_profile = $account_profile->id();
    }

    // Join the event tables.
    $configuration = [
      'table' => 'event_enrollment__field_event',
      'field' => 'field_event_target_id',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $query->addRelationship('event_enrollment__field_event', $join, 'node_field_data');

    $configuration = [
      'table' => 'event_enrollment_field_data',
      'field' => 'id',
      'left_table' => 'event_enrollment__field_event',
      'left_field' => 'entity_id',
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $query->addRelationship('event_enrollment_field_data', $join, 'node_field_data');

    $configuration = [
      'table' => 'event_enrollment__field_enrollment_status',
      'field' => 'entity_id',
      'left_table' => 'event_enrollment__field_event',
      'left_field' => 'entity_id',
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $query->addRelationship('event_enrollment__field_enrollment_status', $join, 'node_field_data');

    $configuration = [
      'table' => 'event_enrollment__field_account',
      'field' => 'entity_id',
      'left_table' => 'event_enrollment__field_event',
      'left_field' => 'entity_id',
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\JoinPluginBase $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $query->addRelationship('event_enrollment__field_account', $join, 'node_field_data');

    $or_condition = new Condition('OR');

    // Check if the user is the author of the event.
    $event_creator = new Condition('AND');
    $event_creator->condition('node_field_data.uid', $account_profile, '=');
    $event_creator->condition('node_field_data.type', 'event', '=');
    $or_condition->condition($event_creator);

    // Or if the user enrolled to the event.
    $enrolled_to_event = new Condition('AND');
    $enrolled_to_event->condition('event_enrollment__field_account.field_account_target_id', $account_profile, '=');
    $enrolled_to_event->condition('event_enrollment__field_enrollment_status.field_enrollment_status_value', '1', '=');
    $or_condition->condition($enrolled_to_event);

    $query->addWhere('enrolled_or_created', $or_condition);

    $this->query = $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache_contexts = parent::getCacheContexts();

    // Since the Stream is different per url.
    if (!in_array('url', $cache_contexts)) {
      $cache_contexts[] = 'url';
    }

    return $cache_contexts;
  }

}
