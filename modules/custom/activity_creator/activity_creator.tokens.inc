<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function activity_creator_token_info() {
  $type = [
    'name' => t('Activity Entity tokens'),
    'description' => t('Tokens from the activity creator module.'),
    'needs-data' => 'activity',
  ];

  $activity['field_activity_recipient_user_display_name'] = [
    'name' => t("Recipient user entity display name"),
    'description' => t("The recipient user entity display name."),
  ];

  return [
    'types' => ['activity' => $type],
    'tokens' => [
      'activity' => $activity,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function activity_creator_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'activity' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'field_activity_recipient_user_display_name':

          // Get the targeted user, and his display name.
          if ($name === 'field_activity_recipient_user_display_name') {
            if (isset($message->field_message_related_object)) {
              $target_type = $message->field_message_related_object->target_type;
              $target_id = $message->field_message_related_object->target_id;
              $entity = \Drupal::entityTypeManager()
                ->getStorage($target_type)
                ->load($target_id);

              // Or special handling for post entities.
              if ($entity->getEntityTypeId() === 'post') {
                if (isset($entity->field_recipient_user) && !empty($entity->field_recipient_user->target_id)) {
                  $target_id = $entity->field_recipient_user->target_id;
                  $recipient_user = \Drupal::entityTypeManager()
                    ->getStorage('user')
                    ->load($target_id);

                  if (!empty($recipient_user)) {
                    /** @var \Drupal\user\Entity\User $recipient_user_entity */
                    $replacements[$original] = $recipient_user->getDisplayName();
                  }
                }
              }
            }
          }
          break;
      }
    }
  }

  return $replacements;
}
