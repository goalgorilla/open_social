<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Comment module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function social_comment_token_info() {

  $type = [
    'name' => t('Social Comment'),
    'description' => t('Tokens from the social comment module.'),
  ];

  $social_comment['parent_entity_author'] = [
    'name' => t('The author of the parent entity.'),
    'description' => t('The author of the content type that was commented on.'),
  ];

  $social_comment['commented_content_type'] = [
    'name' => t('The commented content type.'),
    'description' => t('The type of the content that was commented on.'),
  ];

  return [
    'types' => ['social_comment' => $type],
    'tokens' => [
      'social_comment' => $social_comment,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_comment_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_comment' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

      foreach ($tokens as $name => $original) {
        switch ($name) {

          case 'parent_entity_author':

            if ($name === 'parent_entity_author') {
              if (isset($message->field_message_related_object)) {
                $target_type = $message->field_message_related_object->target_type;
                $target_id = $message->field_message_related_object->target_id;
                $comment = \Drupal::entityTypeManager()
                  ->getStorage($target_type)
                  ->load($target_id);

                // Or special handling for post entities.
                if ($comment->getEntityTypeId() == 'comment') {
                  if (!empty($comment->getCommentedEntity())) {
                    $node = $comment->getCommentedEntity();
                    $owner = $node->getOwner();
                    $name = $owner->getDisplayName();

                    if (!empty($name)) {
                      $replacements[$original] = $name;
                    }
                  }
                }
              }
            }

            break;

          case 'commented_content_type':

            if($name == 'commented_content_type') {
              if(isset($message->field_message_related_object)) {
                $target_type = $message->field_message_related_object->target_type;
                $target_id = $message->field_message_related_object->target_id;
                $comment = \Drupal::entityTypeManager()
                  ->getStorage($target_type)
                  ->load($target_id);

                if ($comment->getEntityTypeId() == 'comment') {
                  if (!empty($comment->getCommentedEntity())) {
                    $node = $comment->getCommentedEntity();
                    $bundle = $node->bundle();

                    if (!empty($bundle)) {
                      $replacements[$original] = $bundle;
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
