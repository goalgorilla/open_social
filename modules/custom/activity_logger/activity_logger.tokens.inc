<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageInterface;

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
  $message['pmt-url'] = [
    'name' => t("Private Message Thread url"),
    'description' => t("The URL of the private message thread group url."),
  ];

  $message['recipient-user'] = [
    'name' => t('Recipient user'),
    'description' => t('The recipient user.'),
    'type' => 'user',
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

  if ($type === 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    $token_service = \Drupal::token();

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'node-title':
        case 'gtitle':
        case 'gurl':
        case 'recipient-user':
        case 'recipient-user-url':
        case 'pmt-url':

          if (!empty($message->get('field_message_related_object'))) {
            $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
            $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {
              // If comment get the entity to which the comment is attached.
              if ($entity->getEntityTypeId() === 'comment') {
                $entity = $entity->getCommentedEntity();

                // It could happen that a notification has been queued but by
                // now the "commented entity" has been deleted.
                if (!$entity instanceof FieldableEntityInterface) {
                  break;
                }
              }

              // When it is a node.
              if ($entity->getEntityTypeId() === 'node') {
                $node = $entity;
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
                  $group = Group::load($entity->field_recipient_group->target_id);
                }
                if (isset($entity->field_recipient_user) && !empty($entity->field_recipient_user->target_id)) {
                  $target_id = $entity->field_recipient_user->target_id;
                  $recipient_user = \Drupal::entityTypeManager()
                    ->getStorage('user')
                    ->load($target_id);
                }
              }
              // Handling for group content entities.
              if ($entity->getEntityTypeId() === 'group_content') {
                $group = $entity->getGroup();
                $group_content_entity = $entity->getEntity();

                switch ($group_content_entity->getEntityTypeId()) {
                  case 'node':
                    $node = &$group_content_entity;
                    break;

                  case 'user':
                    $recipient_user = &$group_content_entity;
                    break;
                }
              }
              // Handling for group entities.
              if ($entity->getEntityTypeId() === 'group') {
                $group = $entity;
              }

              // If it's a group.. add it in the arguments.
              if (isset($group) && $group instanceof Group) {
                if ($name === 'gtitle') {
                  $curr_langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
                  if ($group->isTranslatable() && $group->hasTranslation($curr_langcode)) {
                    $group = $group->getTranslation($curr_langcode);
                  }
                  $replacements[$original] = $group->label();
                }
                if ($name === 'gurl') {
                  $gurl = Url::fromRoute('entity.group.canonical',
                    ['group' => $group->id()],
                    ['absolute' => TRUE]
                  );
                  $replacements[$original] = $gurl->toString();
                }
              }

              if ($name === 'recipient-user') {
                if (!empty($recipient_user)) {
                  $account = &$recipient_user;
                }
                else {
                  $account = \Drupal::entityTypeManager()->getStorage('user')
                    ->load(0);
                }

                /** @var \Drupal\user\UserInterface $account */
                $replacements[$original] = $account->getDisplayName();
              }
              elseif ($name === 'recipient-user-url') {
                if (!empty($recipient_user)) {
                  $target_stream_url = Url::fromRoute('entity.user.canonical',
                    ['user' => $recipient_user->id()],
                    ['absolute' => TRUE]
                  );
                  $replacements[$original] = $target_stream_url->toString();
                }
              }

              if ($name === 'pmt-url') {
                // Get the related message.
                $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
                $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
                $entity = \Drupal::entityTypeManager()
                  ->getStorage($target_type)
                  ->load($target_id);
                // Use the mapper service to get the ID of the thread.
                $pmService = \Drupal::service('private_message.mapper');
                $thread_id = $pmService->getThreadIdFromMessage($entity);
                // Build the url.
                $thread_url = Url::fromRoute('entity.private_message_thread.canonical',
                  ['private_message_thread' => $thread_id],
                  ['absolute' => TRUE]
                );
                $replacements[$original] = $thread_url->toString();
              }

              if ($name === 'node-title') {
                if (isset($node) && $node instanceof Node) {
                  $replacements[$original] = $node->label();
                }
              }

            }
          }
          break;
      }
    }

    $recipient_user_tokens = $token_service->findWithPrefix($tokens, 'recipient-user');

    if ($recipient_user_tokens && !empty($recipient_user)) {
      $replacements += $token_service->generate('user', $recipient_user_tokens, ['user' => $recipient_user], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
