<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Event AN Enroll module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

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

  $social_event_invite['user_login_event_destination'] = [
    'name' => t('Event link with login page'),
    'description' => t('Url to the login page with event as destination.'),
  ];

  $social_event_invite['user_login_event_invites_overview'] = [
    'name' => t('Event link with login page'),
    'description' => t('Url to the login page with event as destination.'),
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
      if (
        !empty($data['node']) &&
        $name == 'register_link' &&
        !empty($data['event_enrollment'])
      ) {
        $event = $data['node'];

        // Load the current Event enrollments so we can check duplicates.
        $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $storage->load($data['event_enrollment']);

        $mail = $event_enrollment->get('field_email')->getString();
        $mail_encoded = str_replace(['+', '/', '='], [
          '-',
          '_',
          '',
        ], base64_encode($mail));

        $destination = Url::fromRoute('entity.node.canonical', ['node' => $event->id()])->toString();

        $options = [
          'invitee_mail' => $mail_encoded,
          'destination' => $destination,
        ];

        $register_link = Url::fromRoute('user.register', $options, ['absolute' => TRUE])
          ->toString();
        $replacements[$original] = $register_link;
      }
      if ($name == 'user_login_event_destination') {
        /** @var \Drupal\message\Entity\Message $message */
        $message = $data['message'];

        // Load the current Event enrollments so we can check duplicates.
        $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $storage->load($message->field_message_related_object->target_id);

        if ($event_enrollment) {
          $event = $event_enrollment->get('field_event')->getString();
          $destination = Url::fromRoute('entity.node.canonical', ['node' => $event])
            ->toString();
          $login_link = Url::fromRoute('user.login', ['destination' => $destination], ['absolute' => TRUE])
            ->toString();
          $replacements[$original] = $login_link;
        }
      }
      if ($name == 'user_login_event_invites_overview') {
        /** @var \Drupal\message\Entity\Message $message */
        $message = $data['message'];

        // Load the current Event enrollments so we can check duplicates.
        $storage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $storage->load($message->field_message_related_object->target_id);

        if ($event_enrollment) {
          $user = User::load($event_enrollment->get('field_account')->getString());
          $my_invitations_link = Url::fromRoute('view.user_event_invites.page_user_event_invites', ['user' => $user->id()], ['absolute' => TRUE])->toString();
          $replacements[$original] = $my_invitations_link;
        }
      }
    }
  }
  return $replacements;
}
