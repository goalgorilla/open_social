<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Group module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

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

  $social_group['content_url'] = [
    'name' => t('The group content url.'),
    'description' => t('The url to the content that is created in the group.'),
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

        case 'content_type':

          // Get the related entity.
          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {

              // When it's a post or photo post.
              if ($entity->getEntityTypeId() === 'post' || $entity->getEntityTypeId() === 'photo') {
                if ($name === 'content_type') {
                  if (is_callable([$entity, 'getDisplayName'])) {
                    $display_name = $entity->getDisplayName();
                  }
                  else {
                    $display_name = $entity->bundle();
                  }
                  if (!empty($display_name)) {
                    $replacements[$original] = $display_name;
                  }
                }
              }

              // If it's group content.
              if ($entity->getEntityTypeId() === 'group_content') {
                if ($name === 'content_type') {
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

        case 'content_url':

          // Get the related entity.
          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {
              // If it's group content.
              if ($entity->getEntityTypeId() === 'group_content') {
                if ($name === 'content_url') {
                  $content_url = Url::fromRoute('entity.node.canonical',
                    ['node' => $entity->getEntity()->id()],
                    ['absolute' => TRUE]
                  );
                  $replacements[$original] = $content_url->toString();
                }
              }
            }
          }

      }
    }
  }

  return $replacements;
}
