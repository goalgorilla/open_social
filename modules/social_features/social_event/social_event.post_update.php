<?php

/**
 * @file
 * Post update functions for the Social event module.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Empty post update hook.
 */
function social_event_post_update_update_events(array &$sandbox): void {
  // Moved to social_event_post_update_10301_enable_event_enrollment().
}

/**
 * Set event enrollment option to enabled by default for existing events.
 */
function social_event_post_update_10301_enable_event_enrollment(array &$sandbox): ?TranslatableMarkup {
  /** @var \Drupal\node\NodeStorageInterface $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['total'])) {
    // Get all event ids.
    $sandbox['ids'] = $node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->execute();
    // Write total of entities need to be processed to $sandbox.
    $sandbox['total'] = count($sandbox['ids']);

    // Initiate default value for current processing № of element.
    $sandbox['current'] = 0;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total']) || empty($sandbox['ids'])) {
    $sandbox['#finished'] = 1;
    return t('No events to be processed.');
  }

  // Try to update 25 events at a time.
  $ids = array_slice($sandbox['ids'], $sandbox['current'], 25);

  /** @var \Drupal\node\NodeInterface $event */
  foreach ($node_storage->loadMultiple($ids) as $event) {
    if ($event->hasField('field_event_enable_enrollment')) {
      $event->set('field_event_enable_enrollment', '1');
      $event->save();
    }
    $sandbox['current']++;
  }

  // Try to update the percentage but avoid division by zero.
  $sandbox['#finished'] = $sandbox['total'] == 0 ? 1 : ($sandbox['current'] / $sandbox['total']);
  return NULL;
}

/**
 * Sets all day value for events.
 *
 * Now we do not use the time check if it is an event for all-day, we use the
 * 'All day' field in event, in which case we must make an entry in database
 * (State API) for all events that were for all-day, use the old logic for
 * checking.
 */
function social_event_post_update_10302_set_all_day_value(array &$sandbox): void {
  if (!isset($sandbox['total'])) {
    $sandbox['ids'] = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->accessCheck(FALSE)
      ->execute();

    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['current'] = 0;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total']) || empty($sandbox['ids'])) {
    $sandbox['#finished'] = 1;
  }

  // Try to update 25 events at a time.
  $nids = array_slice($sandbox['ids'], $sandbox['current'], 25);

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $date_formatter = \Drupal::service('date.formatter');

  foreach ($nids as $nid) {
    /** @var \Drupal\node\NodeInterface $event */
    $event = $node_storage->load($nid);
    $start_date_is_full_day = FALSE;
    $end_date_is_full_day = FALSE;

    if (!$event->get('field_event_date')->isEmpty()) {
      $start_datetime = strtotime($event->get('field_event_date')->getString());
      if ($start_datetime) {
        $start_date_is_full_day = $date_formatter->format($start_datetime, 'custom', 'i') === '01';
      }
    }
    if (!$event->get('field_event_date_end')->isEmpty()) {
      $end_datetime = strtotime($event->get('field_event_date_end')->getString());
      if ($end_datetime) {
        $end_date_is_full_day = $date_formatter->format($end_datetime, 'custom', 'i') === '01';
      }
    }

    if (
      $start_date_is_full_day &&
      $end_date_is_full_day &&
      $event->get('field_event_all_day')->isEmpty()
    ) {
      $event->set('field_event_all_day', TRUE);
      $event->save();
    }

    $sandbox['current']++;
  }

  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' nodes processed.', 'status');

  if ($sandbox['total'] === 0) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}

/**
 * Updates the node type visibility condition.
 */
function social_event_post_update_replace_node_type_condition(): void {
  $config_factory = \Drupal::configFactory();

  $block_list = [
    'block.block.socialblue_views_block__event_enrollments_event_enrollments_socialbase',
    'block.block.views_block__event_enrollments_event_enrollments_socialbase',
  ];

  foreach ($block_list as $block_config_name) {
    $block = $config_factory->getEditable($block_config_name);

    if ($block->get('visibility.node_type')) {
      $configuration = $block->get('visibility.node_type');
      $configuration['id'] = 'entity_bundle:node';
      $block->set('visibility.entity_bundle:node', $configuration);
      $block->clear('visibility.node_type');
      $block->save(TRUE);
    }
  }
}
