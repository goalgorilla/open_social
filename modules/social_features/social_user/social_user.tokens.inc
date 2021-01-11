<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Group module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_user_token_info() {
  $type = [
    'name' => t('Social User'),
    'description' => t('Tokens from the social user module.'),
  ];

  $social_user['recipient'] = [
    'name' => t('The display name of the Receiver of the email.'),
    'description' => t('The individual display name of a user, when receiving an email.'),
  ];

  return [
    'types' => ['social_user' => $type],
    'tokens' => [
      'social_user' => $social_user,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_user_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_user' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'recipient':

          if (isset($data['display_name'])) {
            $replacements[$original] = $data['display_name'];
          }
          break;
      }
    }
  }

  return $replacements;
}
