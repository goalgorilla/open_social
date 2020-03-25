<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Event AN Enroll module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function social_event_invite_token_info() {
  $type = [
    'name' => t('Social Event Invite'),
    'description' => t('Tokens from the Social Event Invite module.'),
  ];

  $social_event_invite['register_link'] = [
    'name' => t('Register Link'),
    'description' => t('Url to register page with prefilled email address.'),
  ];

  return [
    'types' => ['social_event_invite' => $type],
    'tokens' => [
      'social_event_invite' => $social_event_invite,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_event_invite_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'social_event_invite') {
    foreach ($tokens as $name => $original) {
      if ($name == 'register_link') {
        // Load the current Event enrollments so we can check duplicates.
        $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $storage->load($data['event_enrollment']);

        $mail = $event_enrollment->get('field_email')->getString();
        $mail_encoded = str_replace(['+', '/', '='], [
          '-',
          '_',
          ''
        ], base64_encode($mail));
        $register_link = Url::fromRoute('user.register', ['invitee_mail' => $mail_encoded], ['absolute' => TRUE])
          ->toString();
        $replacements[$original] = $register_link;
      }
    }
  }
  return $replacements;
}
