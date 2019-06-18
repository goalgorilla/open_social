<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Comment module.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;
use Drupal\Core\Render\Markup;

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

  $social_comment['parent_comment_author'] = [
    'name' => t('The author of the parent comment.'),
    'description' => t('The author of the parent comment.'),
  ];

  $social_comment['parent_comment_date'] = [
    'name' => t('The date of the parent comment.'),
    'description' => t('The date of the parent comment.'),
  ];

  $social_comment['parent_comment_text'] = [
    'name' => t('The text of the parent comment.'),
    'description' => t('The partial text of the parent comment.'),
  ];

  $social_comment['commented_content_type'] = [
    'name' => t('The commented content type.'),
    'description' => t('The type of the content that was commented on.'),
  ];

  $social_mentions['commented_entity_link_html'] = [
    'name' => t('The (html) link to the commented entity.'),
    'description' => t('The link to the commented entity, can be post or node, will include raw HTML.'),
  ];

  $social_comment['comment_author'] = [
    'name' => t('The author of the comment.'),
    'description' => t('The author of the comment.'),
  ];

  $social_comment['comment_date'] = [
    'name' => t('The date of the comment.'),
    'description' => t('The date of the comment.'),
  ];

  $social_comment['comment_text'] = [
    'name' => t('The text of the comment.'),
    'description' => t('The partial text of the comment.'),
  ];

  $social_comment['comment_reply_link_html'] = [
    'name' => t('The (html) link to reply to the comment.'),
    'description' => t('The link (button) to reply to the comment, will include raw HTML.'),
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
        case 'parent_comment_author':
        case 'parent_comment_date':
        case 'parent_comment_text':
        case 'comment_author':
        case 'comment_date':
        case 'comment_text':
        case 'comment_reply_link_html':

          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $comment = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            // Or special handling for post entities. \Drupal::logger('commented_content_type')->notice(var_dump($comment));
            if (!empty($comment)) {
              if ($comment->getEntityTypeId() == 'comment') {
                $date_formatter = \Drupal::service('date.formatter');
                $date_format = 'social_long_date';

                if ($comment->hasParentComment()) {
                  $parent_comment = $comment->getParentComment();

                  if ($name === 'parent_comment_author') {
                    $replacements[$original] = $parent_comment->getOwner()->getDisplayName();
                  }

                  if ($name === 'parent_comment_date') {
                    $replacements[$original] = $date_formatter->format($parent_comment->getCreatedTime(), $date_format);
                  }

                  if ($name === 'parent_comment_text') {
                    $replacements[$original] = $parent_comment->field_comment_body->value;
                  }
                }

                if (!empty($comment->getCommentedEntity())) {
                  $commented_entity = $comment->getCommentedEntity();
                  if ($name === 'parent_entity_author') {
                    $replacements[$original] = $commented_entity->getOwner()->getDisplayName();
                  }
                }

                if ($name === 'comment_author') {
                  $replacements[$original] = $comment->getOwner()->getDisplayName();
                }

                if ($name === 'comment_date') {
                  $replacements[$original] = $date_formatter->format($comment->getCreatedTime(), $date_format);
                }

                if ($name === 'comment_text') {
                  $replacements[$original] = _social_comment_get_half_text($comment);
                }

                if ($name === 'comment_reply_link_html') {
                  $replacements[$original] = Url::fromRoute('comment.reply', [
                    'entity_type' => $comment->getCommentedEntityTypeId(),
                    'entity' => $comment->getCommentedEntityId(),
                    'field_name' => $comment->getFieldName(),
                    'pid' => $comment->id(),
                  ])->toString();
                }

              }
            }
          }

          break;

        case 'commented_content_type':
        case 'commented_entity_link_html':

          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            /** @var \Drupal\comment\Entity\Comment $comment */
            $comment = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (!empty($comment) && $comment->getEntityTypeId() == 'comment') {
              /** @var \Drupal\Core\Entity\EntityBase $entity */
              $entity = $comment->getCommentedEntity();
              if (!empty($entity)) {
                if ($entity instanceof PostInterface) {
                  $commented_content_type = Unicode::strtolower($entity->getEntityType()->getLabel());
                }
                elseif (is_callable([$entity, 'getDisplayName'])) {
                  $commented_content_type = $entity->getDisplayName();
                }
                else {
                  if ($entity instanceof NodeInterface) {
                    $commented_content_type = strtolower($entity->type->entity->label());
                  }
                  else {
                    $commented_content_type = $entity->bundle();
                  }
                }

                if ($name === 'commented_content_type') {
                  if (!empty($commented_content_type)) {
                    $replacements[$original] = $commented_content_type;
                  }
                }
                else {
                  $url_options = ['absolute' => TRUE];
                  $link = $entity->toUrl('canonical', $url_options)->toString();

                  // We should only use the label of entities who have a label.
                  if ($link_label = $entity->label()) {
                    $entity_link_html = $commented_content_type . ' <a href="' . $link . '">' . $link_label . '</a>';
                  }
                  else {
                    $entity_link_html = '<a href="' . $link . '">' . $commented_content_type . '</a>';
                  }

                  $replacements[$original] = Markup::create($entity_link_html);
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

/**
 * Get half of the comment body().
 */
function _social_comment_get_half_text($comment) {
  $half_text = '';
  if (isset($comment->field_comment_body) && !empty($comment->field_comment_body->value)) {
    $text = $comment->field_comment_body->value;
    $middle = strrpos(substr($text, 0, floor(strlen($text) / 2)), ' ') + 1;
    $half_text = substr($text, 0, $middle) . '...';
  }
  return $half_text;
}
