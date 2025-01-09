<?php

/**
 * @file
 * Post update functions for the Social event module.
 */

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Empty post update hook.
 */
function social_event_post_update_update_events(&$sandbox) {
  // Moved to social_event_post_update_10301_enable_event_enrollment().
}

/**
 * Set event enrollment option to enabled by default for existing events.
 */
function social_event_post_update_10301_enable_event_enrollment(&$sandbox) {
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
  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);
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

  if ($sandbox['total'] == 0) {
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

/**
 * Remove stale event enrollments that still link to deleted user accounts.
 *
 * Scenario 1:
 *
 * Logged in as Site Manager.
 * Created an event.
 * Add a user via Directly adding them to event.
 *
 * Result
 * A new event enrollment has been created where userID is Site Manager’s UID
 * and field_account is of the added user.
 *
 * Scenario 2:
 *
 * Logged in as Site Manager.
 * Created an event.
 * Invited a user via email adding them to event.
 *
 * Result
 * A new event enrollment has been created where userID is Site Manager’s UID
 * and field_account is of the invited user.
 *
 *
 * Scenario 3:
 *
 * Logged in as a user.
 * Joined an existing event.
 *
 * Result
 * A new event enrollment has been created where userID and field_account is
 * user’s own UID.
 *
 * Previously, in social_event_user_delete() didn’t delete event enrollments
 * even if the user is removed from the system, as event enrollments were
 * created due to Scenario 1 and 2.
 *
 * @see https://git.drupalcode.org/project/social/-/blob/12.3.10/modules/social_features/social_event/social_event.module?ref_type=tags#L546
 */
function social_event_post_update_10303_remove_stale_event_enrollment(array &$sandbox): TranslatableMarkup|PluralTranslatableMarkup {
  $event_enrollment_storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');

  if (!isset($sandbox['total'])) {
    // Get all event enrollment ids.
    $sandbox['ids'] = $event_enrollment_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->execute();
    // Write total of entities need to be processed to $sandbox.
    $sandbox['total'] = count($sandbox['ids']);

    // Initiate default value for current processing № of element.
    $sandbox['current'] = 0;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total']) || empty($sandbox['ids'])) {
    $sandbox['#finished'] = 1;
    return new TranslatableMarkup('No event enrollments to be processed.');
  }

  // Try to update 25 events at a time.
  $ids = array_slice($sandbox['ids'], $sandbox['current'], 25);

  /** @var \Drupal\social_event\Entity\EventEnrollment $event_enrollment */
  foreach ($event_enrollment_storage->loadMultiple($ids) as $event_enrollment) {
    if ($event_enrollment->hasField('field_account')) {
      $user = $event_enrollment->get('field_account')->getValue();
      if (is_array($user) && array_key_exists('target_id', $user)) {
        $user_id = $event_enrollment->get('field_account')->getValue()[0]['target_id'];

        // If no user entity is found, then delete the enrollment.
        if (empty($user_id) || !\Drupal::entityTypeManager()->getStorage('user')->load($user_id)) {
          $event_enrollment->delete();
        }
      }
    }
    $sandbox['current']++;
  }

  // Try to update the percentage but avoid division by zero.
  $sandbox['#finished'] = $sandbox['current'] / $sandbox['total'];

  if ($sandbox['#finished'] === 1) {
    return new TranslatableMarkup('Finished deleting stale event enrollments.');
  }

  return new PluralTranslatableMarkup($sandbox['current'],
    'Processed @count entry of @total.',
    'Processed @count entries of @total.',
    ['@total' => $sandbox['total']],
  );
}
