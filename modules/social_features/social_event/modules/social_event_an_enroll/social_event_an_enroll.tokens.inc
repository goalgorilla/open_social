<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Event AN Enroll module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_event_an_enroll_token_info() {

  $type = [
    'name' => t('Social Event AN Enroll'),
    'description' => t('Tokens from the Social Event AN Enroll module.'),
  ];

  $social_event_an_enroll['enrolled_event'] = [
    'name' => t('URL of anonymously enrolled event.'),
    'description' => t('URL of anonymously enrolled event with token to cancel enrollment.'),
  ];

  return [
    'types' => ['social_event_an_enroll' => $type],
    'tokens' => [
      'social_event_an_enroll' => $social_event_an_enroll,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_event_an_enroll_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'social_event_an_enroll' && !empty($data['node']) && !empty($data['token'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'enrolled_event':
          // Returns url to event with token for anonymous enrollment.
          $url_options = [
            'query' => ['token' => $data['token']],
            'absolute' => TRUE,
          ];
          $replacements[$original] = $data['node']->toUrl('canonical', $url_options)->toString();
          break;
      }
    }
  }
  return $replacements;
}
