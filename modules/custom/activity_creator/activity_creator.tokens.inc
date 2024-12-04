<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\message\Entity\Message;
use Drupal\user\UserInterface;

/**
 * Implements hook_token_info().
 */
function activity_creator_token_info(): array {
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
function activity_creator_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type === 'activity' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    if ($message instanceof Message) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'field_activity_recipient_user_display_name':
            // Get the targeted user and its display name.
            if ($name === 'field_activity_recipient_user_display_name') {
              if (!$message->get('field_message_related_object')->isEmpty()) {
                $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
                $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
                /** @var \Drupal\Core\Entity\ContentEntityInterface|NULL $entity */
                $entity = \Drupal::entityTypeManager()
                  ->getStorage($target_type)
                  ->load($target_id);
                if ($entity === NULL) {
                  break;
                }

                // Or special handling for post entities.
                if ($entity->getEntityTypeId() === 'post') {
                  if (
                    $entity->get('field_recipient_user') !== NULL
                    && !empty($entity->get('field_recipient_user')->target_id)) {
                    $target_id = $entity->get('field_recipient_user')->target_id;
                    $recipient_user = \Drupal::entityTypeManager()
                      ->getStorage('user')
                      ->load($target_id);

                    if ($recipient_user instanceof UserInterface) {
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
  }

  return $replacements;
}
