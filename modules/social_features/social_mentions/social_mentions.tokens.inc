<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Mentions module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_mentions_token_info() {
  $type = [
    'name' => t('Social Mentions'),
    'description' => t('Tokens from the social mentions module.'),
    'needs-data' => 'profile',
  ];

  $social_mentions['user_name'] = [
    'name' => t('User name'),
    'description' => t('First and last name or username, depends on settings.'),
  ];

  return [
    'types' => ['social_mentions' => $type],
    'tokens' => [
      'social_mentions' => $social_mentions,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_mentions_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_mentions' && !empty($data['profile'])) {
    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = $data['profile'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'user_name':

          $config = \Drupal::config('mentions.settings');
          switch ($config->get('suggestions_format')) {
            case SOCIAL_MENTIONS_SUGGESTIONS_FULL_NAME:
            case SOCIAL_MENTIONS_SUGGESTIONS_ALL:
              if (!empty($profile->field_profile_first_name->value) && !empty($profile->field_profile_last_name->value)) {
                $user_name = t('@first @last', array(
                  '@first' => $profile->field_profile_first_name->value,
                  '@last' => $profile->field_profile_last_name->value
                ));
              }
          }
          if (empty($user_name)) {
            $user_name = $profile->getOwner()->getAccountName();
          }

          $replacements[$original] = $user_name;
          break;
      }
    }
  }

  return $replacements;
}
