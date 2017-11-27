<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Group module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_token_info().
 */
function social_group_token_info() {

  $type = [
    'name' => t('Social Group'),
    'description' => t('Tokens from the social group module.'),
  ];

  $social_group['content_type'] = [
    'name' => t('The group content type.'),
    'description' => t('The type of the content that is created in the group.'),
  ];

  $social_group['group_name'] = [
    'name' => t('The group name.'),
    'description' => t('The name of the group.'),
  ];

  return [
    'types' => ['social_group' => $type],
    'tokens' => [
      'social_group' => $social_group,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_group' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'group_name':
        case 'group_url':
        case 'content_type':

          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {

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
              }

              // If it's a group.. add it in the arguments.
              if (isset($group) && $group instanceof Group) {
                if ($name === 'group_name') {
                  $replacements[$original] = $group->label();
                }
                if ($name === 'group_url') {
                  $group_url = Url::fromRoute('entity.group.canonical', ['group' => $group->id()]);
                  $replacements[$original] = $group_url->toString();
                }
              }

              if ($name == 'content_type') {

                if ($entity->getEntityTypeId() == 'post' || $entity->getEntityTypeId() == 'photo') {
                  $replacements[$original] = t('post');
                }

                if ($entity->getEntityTypeId() == 'group_content') {
                  /** @var \Drupal\group\Entity\GroupContent $entity */
                  $group_content_type = $entity->getGroupContentType();
                  if (!empty($group_content_type)) {
                    $replacements[$original] = $group_content_type->label();
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
