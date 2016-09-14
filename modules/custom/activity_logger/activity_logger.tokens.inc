<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

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

  $message['recipient-user-url'] = [
    'name' => t("Recipient user url"),
    'description' => t("The recipient user url."),
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
  $replacements = [];

  if ($type == 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'gtitle':
        case 'gurl':
        case 'recipient-user-url':

          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            // If comment get the entity to which the comment is attached.
            if ($entity->getEntityTypeId() === 'comment') {
              $entity = $entity->getCommentedEntity();
            }

            // Try to get the group.
            $group_content = GroupContent::loadByEntity($entity);
            if (!empty($group_content)) {
              $group_content = reset($group_content);
              $group = $group_content->getGroup();
            }
            // Or special handling for post entities.
            if ($entity->getEntityTypeId() === 'post') {
              if (!empty($entity->get('field_recipient_group')->getValue())) {
                $group = Group::load($group_id = $entity->field_recipient_group->target_id);
              }
              if (isset($entity->field_recipient_user) && !empty($entity->field_recipient_user->target_id)) {
                $target_id = $entity->field_recipient_user->target_id;
                $recipient_user = \Drupal::entityTypeManager()
                  ->getStorage('user')
                  ->load($target_id);
              }
            }

            // If it's a group.. add it in the arguments.
            if ($group instanceof Group) {
              if ($name === 'gtitle') {
                $replacements[$original] = $group->label();
              }
              if ($name === 'gurl') {
                $gurl = Url::fromRoute('entity.group.canonical', array(
                  'group' => $group->id(),
                  array()
                ));
                $replacements[$original] = $gurl->toString();
              }
            }

            if ($name === 'recipient-user-url') {
              if (!empty($recipient_user)) {
                $target_stream_url = Url::fromRoute('entity.user.canonical',
                  array('user' => $recipient_user->id()),
                  array('absolute' => TRUE)
                );
                $replacements[$original] = $target_stream_url->toString();
              }
            }

          }
          break;
      }
    }
  }

  return $replacements;
}
