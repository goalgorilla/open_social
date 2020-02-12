<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_queue_storage_token_info() {
  $tokens['mail_task_subject'] = [
    'name' => t('Email task subject'),
    'description' => t('The subject of the email task that ran in the background.'),
  ];

  return [
    'types' => [
      'queue_storage_entity' => [
        'name' => t('Social Queue Entity'),
        'description' => t('Tokens from the Social Queue Entity module.'),
      ],
    ],
    'tokens' => [
      'queue_storage_entity' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_queue_storage_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'queue_storage_entity' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      if ($name === 'mail_task_subject' && $message->getFieldValue('field_message_related_object', 'target_type') === 'queue_storage_entity') {
        // Get the target storage entity and get the subject.
        $target_type = $message->get('field_message_related_object')->target_type;
        $target_id = $message->get('field_message_related_object')->target_id;

        /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $entity */
        $entity = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->load($target_id);

        if ($entity->hasField('field_subject')) {
          $replacements[$original] = $entity->get('field_subject')->value;
        }
      }
    }
  }

  return $replacements;
}
