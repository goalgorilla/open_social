<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Private Message.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_private_message_token_info() {
  $type = [
    'name' => t('Social Private Message'),
    'description' => t('Tokens from the social private message module.'),
    'needs-data' => 'private message',
  ];

  $social_private_message['url'] = [
    'name' => t('Url of the thread'),
    'description' => t('Get the url of the thread'),
  ];

  return [
    'types' => ['social_private_message' => $type],
    'tokens' => [
      'social_private_message' => $social_private_message,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_private_message_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  $x = 1;
  if ($x == 1) {
    $pmdata = $data;
  }

  if ($type == 'social_private_message') {

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'url':
          $url = 'test';
          $replacements[$original] = $url;
          break;

      }
    }
  }

  return $replacements;
}
