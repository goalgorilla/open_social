<?php

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
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
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Query for the activity stream on the account pages.
   */
  public function query() {
    // Profile user.
    $account_profile = \Drupal::routeMatch()->getParameter('user');

    if (!is_null($account_profile) && is_object($account_profile)) {
      $account_profile = $account_profile->id();
    }

    // Join the event tables.
    $configuration = array(
      'table' => 'event_enrollment__field_event',
      'field' => 'field_event_target_id',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('event_enrollment__field_event', $join, 'node_field_data');

    $configuration = array(
      'table' => 'event_enrollment',
      'field' => 'id',
      'left_table' => 'event_enrollment__field_event',
      'left_field' => 'entity_id',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('event_enrollment', $join, 'node_field_data');

    $configuration = array(
      'table' => 'event_enrollment__field_enrollment_status',
      'field' => 'entity_id',
      'left_table' => 'event_enrollment__field_event',
      'left_field' => 'entity_id',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('event_enrollment__field_enrollment_status', $join, 'node_field_data');

    $or_condition = db_or();

    // Check if the user is the author of the event.
    $event_creator = db_and();
    $event_creator->condition('node_field_data.uid', $account_profile, '=');
    $event_creator->condition('node_field_data.type', 'event', '=');
    $or_condition->condition($event_creator);

    // Or if he enrolled to the event.
    $enrolled_to_event = db_and();
    $enrolled_to_event->condition('event_enrollment.user_id', $account_profile, '=');
    $enrolled_to_event->condition('event_enrollment__field_enrollment_status.field_enrollment_status_value', '1', '=');
    $or_condition->condition($enrolled_to_event);

    $this->query->addWhere('enrolled_or_created', $or_condition);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    // Since the Stream is different per url.
    if (!in_array('url', $cache_contexts)) {
      $cache_contexts[] = 'url';
    }

    return $cache_contexts;
  }

}
