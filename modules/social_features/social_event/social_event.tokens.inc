<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Event module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;

/**
 * Implements hook_token_info().
 */
function social_event_token_info() {

  $type = [
    'name' => t('Social Event'),
    'description' => t('Tokens from the Social Event module.'),
  ];

  $social_event['event_iam_organizing'] = [
    'name' => t('URL of the Event I organize.'),
    'description' => t('URL of the Event I organize.'),
  ];

  $social_event['enrolled_user'] = [
    'name' => t('Username of user enrolled to the event.'),
    'description' => t('Username of user enrolled to the event. Can be AN.'),
  ];

  $social_event['enrolled_event_url'] = [
    'name' => t('Url of the Event a user enrolled to.'),
    'description' => t('Url of the Event a user enrolled to.'),
  ];

  return [
    'types' => ['social_event' => $type],
    'tokens' => [
      'social_event' => $social_event,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_event_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type === 'social_event' && !empty($data['message'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'event_iam_organizing':
          $enrollment = $data['message']->getFieldValue('field_message_related_object', 'target_id');
          if (!empty($enrollment)) {
            $link = getLinkToEventFromEnrollment($enrollment, false);

            if ($link !== null) {
              $replacements[$original] = $link;
            }
          }
          break;

        case 'enrolled_event_url':
          $enrollment = $data['message']->getFieldValue('field_message_related_object', 'target_id');
          if (!empty($enrollment)) {
            $link = getLinkToEventFromEnrollment($enrollment, true);

            if ($link !== null) {
              $replacements[$original] = $link;
            }
          }
          break;

        case 'enrolled_user':
          $id = $data['message']->getFieldValue('field_message_related_object', 'target_id');
          if (!empty($id)) {
            $enrollment_name = getNameFromEnrollment($id);

            $replacements[$original] = $enrollment_name;
          }
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Render a link to an event as full link with title or just absolute as string.
 *
 * @param int $event_id
 * @param bool $as_link
 *
 * @return string|NULL
 *   the Url.
 */
function getLinkToEventFromEnrollment($object_id, $as_link = FALSE) {
  $entity_storage = \Drupal::entityTypeManager()
    ->getStorage('event_enrollment');
  /** @var \Drupal\social_event\Entity\EventEnrollment $entity */
  $entity = $entity_storage->load($object_id);
  if ($entity !== null) {
    $event_id = $entity->getFieldValue('field_event', 'target_id');
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\Entity\Node $event */
    $event = $storage->load($event_id);

    if ($as_link) {
      return $event->toUrl('canonical')->toString();
    }

    return Link::fromTextAndUrl($event->getTitle(), $event->toUrl('canonical'))->toString();
  }

  return NULL;
}

/**
 * Get the user display name from an Enrollment also for AN.
 *
 * @param int $id
 *   the Enrollment entity ID.
 *
 * @return array|\Drupal\Component\Render\MarkupInterface|mixed|null|string
 *   Markup for the Username.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function getNameFromEnrollment($id) {
  $entity_storage = \Drupal::entityTypeManager()
    ->getStorage('event_enrollment');
  /** @var \Drupal\social_event\Entity\EventEnrollment $entity */
  $enrollment = $entity_storage->load($id);

  // User is AN by default.
  $enrollment_name = \Drupal::configFactory()->get('user.settings')->get('anonymous');

  // If there is a Uid. Lets load the user and return his display name.
  if ($enrollment->hasField('field_account') &&
    $enrollment->getFieldValue('field_account', 'target_id') > 0) {
    $entity_storage = \Drupal::entityTypeManager()
      ->getStorage('user');
    /** @var \Drupal\user\Entity\User $user */
    $user = $entity_storage->load($enrollment->getFieldValue('field_account', 'target_id'));
    return $user->getDisplayName();
  }

  // User is AN but has enrollment settings available, lets see if we can get
  // the Firstname and lastname to show up in the notification.
  if ($enrollment !== NULL &&
    $enrollment->hasField('field_first_name') &&
    $enrollment->getFieldValue('field_first_name', 'value') !== NULL &&
    $enrollment->getFieldValue('field_last_name', 'value') !== NULL) {
    // Create the Name from AN Attendees if possible.
    $enrollment_name = $enrollment->getFieldValue('field_first_name', 'value') .
      ' ' . $enrollment->getFieldValue('field_last_name', 'value');
  }

  return $enrollment_name;
}
