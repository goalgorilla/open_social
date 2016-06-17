<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function activity_logger_token_info() {
  $type = [
    'name' => t('Activity tokens'),
    'description' => t('Tokens from the activity logger module.'),
    'needs-data' => 'message',
  ];

  $message['node-title'] = [
    'name' => t("Node title"),
    'description' => t("The related node title."),
  ];

  $message['gtitle'] = [
    'name' => t("Groups title"),
    'description' => t("The related group title."),
  ];
  $message['gurl'] = [
    'name' => t("Groups url"),
    'description' => t("The related group url."),
  ];

  return [
    'types' => ['message' => $type],
    'tokens' => [
      'message' => $message,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function activity_logger_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = [];

  if ($type == 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    // Fetch arguments from group.
    $arguments = $message->getArguments();
    $group = $arguments['groups'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'gtitle':
          // Set the group title.
          $replacements[$original] = $group['gtitle'];
          break;
        case 'gurl':
          // Set the group title.
          $replacements[$original] = $group['gurl'];
          break;
      }
    }
  }

  return $replacements;
}
