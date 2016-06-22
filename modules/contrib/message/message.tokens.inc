<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function message_token_info() {
  $type = [
    'name' => t('messages'),
    'description' => t('Tokens related to individual content items, or "messages".'),
    'needs-data' => 'message',
  ];

  // Core tokens for messages.
  $message['mid'] = [
    'name' => t("Message ID"),
    'description' => t('The unique ID of the message.'),
  ];
  $message['type'] = [
    'name' => t("Message type"),
  ];
  $message['type-name'] = [
    'name' => t("message type name"),
    'description' => t("The human-readable name of the message type."),
  ];
  $message['uuid'] = [
    'name' => t("Message UID"),
    'description' => t("The message UUID."),
  ];

  $message['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $message['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];

  return [
    'types' => ['message' => $type],
    'tokens' => ['message' => $message],
  ];
}

/**
 * Implements hook_tokens().
 */
function message_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the message.
        case 'mid':
          $replacements[$original] = $message->id();
          break;

        case 'type':
          $replacements[$original] = $message->getType()->id();
          break;

        case 'type-name':
          $replacements[$original] = $message->getType()->label();
          break;

        case 'uuid':
          $replacements[$original] = $message->getUUID();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $message->getOwner() ? $message->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($message->getCreatedTime(), 'medium', '', NULL);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $message->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $message->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $message->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
