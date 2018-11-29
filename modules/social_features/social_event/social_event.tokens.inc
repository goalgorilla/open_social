<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Event module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
          $entity_storage = \Drupal::entityTypeManager()
            ->getStorage('event_enrollment');
          /** @var \Drupal\social_event\Entity\EventEnrollment $entity */
          $entity = $entity_storage->load($enrollment);
          if ($entity !== null) {
            $event_id = $entity->getFieldValue('field_event', 'target_id');
            $storage = \Drupal::entityTypeManager()->getStorage('node');
            /** @var \Drupal\node\Entity\Node $event */
            $event = $storage->load($event_id);

            $link = Link::fromTextAndUrl($event->getTitle(), $event->toUrl('canonical'))->toString();

            $replacements[$original] = $link;
          }
          break;
      }
    }
  }
  return $replacements;
}
