<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Comment module.
 */

use Drupal\comment\CommentInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

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

  if ($type === 'social_comment' && !empty($data['message'])) {
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

          if (!empty($message->get('field_message_related_object'))) {
            $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
            $target_id = $message->getFieldValue('field_message_related_object', 'target_id');

            $comment = \Drupal::entityTypeManager()->getStorage($target_type)
              ->load($target_id);

            if ($comment instanceof CommentInterface) {
              if ($comment->getEntityTypeId() === 'comment') {
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
                    if ($parent_comment->hasField('field_comment_body') && !$parent_comment->field_comment_body->isEmpty()) {
                      $replacements[$original] = _social_comment_get_summary($parent_comment->field_comment_body->value);
                    }
                    else {
                      $replacements[$original] = '';
                    }
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
                  if ($comment->hasField('field_comment_body') && !$comment->field_comment_body->isEmpty()) {
                    $replacements[$original] = _social_comment_get_summary($comment->field_comment_body->value);
                  }
                  else {
                    $replacements[$original] = '';
                  }
                }

                if ($name === 'comment_reply_link_html') {
                  /** @var \Drupal\Core\Entity\Entity $entity */
                  $entity = $comment->getCommentedEntity();
                  $commented_entity_type = $commented_entity->getEntityTypeId();

                  // A reply link for a post should go to the post view.
                  if ($commented_entity_type === 'post') {
                    $replacements[$original] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
                  }
                  else {
                    // For comments however it should go to comment reply page.
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
          }

          break;

        case 'commented_content_type':
        case 'commented_entity_link':
        case 'commented_entity_link_html':

          if (!empty($message->get('field_message_related_object'))) {
            $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
            $target_id = $message->getFieldValue('field_message_related_object', 'target_id');

            $comment = \Drupal::entityTypeManager()->getStorage($target_type)
              ->load($target_id);

            if ($comment instanceof CommentInterface) {
              /** @var \Drupal\Core\Entity\EntityInterface $entity */
              $entity = $comment->getCommentedEntity();
              if (!empty($entity)) {
                if ($entity instanceof PostInterface) {
                  $commented_content_type = mb_strtolower($entity->getEntityType()->getLabel());
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
                if ($name === 'commented_entity_link') {
                  $replacements[$original] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
                }
                if ($name === 'commented_entity_link_html') {
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
 * Implements hook_tokens_alter().
 */
function social_comment_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'message' && !empty($context['data']['message'])) {
    /** @var Drupal\message\Entity\Message $message */
    $message = $context['data']['message'];

    // Alter the [message:cta_button] token, and replace it with the rendered
    // content with new text and link.
    if (isset($context['tokens']['cta_button']) || isset($context['tokens']['preview'])) {
      if (isset($message->field_message_related_object)) {

        /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
        $email_token_services = \Drupal::service('social_activity.email_token_services');

        /** @var Drupal\comment\Entity\Comment $comment */
        $comment = $email_token_services->getRelatedObject($message);

        if ($comment instanceof CommentInterface && $commented_entity = $comment->getCommentedEntity()) {
          $message_template_id = $message->getTemplate()->id();
          switch ($message_template_id) {
            case 'create_comment_author_node_post':
            case 'create_comment_post_profile':
            case 'create_comment_following_node':
            case 'create_comment_reply':
              // Replace the cta_button token value.
              if (isset($context['tokens']['cta_button'])) {
                /** @var \Drupal\Core\Entity\EntityInterface $commented_entity */
                $link = $commented_entity->toUrl('canonical', ['absolute' => TRUE])->toString();

                $cta_button = $email_token_services->getCtaButton($link, 'Reply to this comment');
                $replacements[$context['tokens']['cta_button']] = \Drupal::service('renderer')->renderPlain($cta_button);
              }

              // Replace the preview token.
              if (isset($context['tokens']['preview'])) {
                $preview_info = $email_token_services->getCommentPreview($comment);
                $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')->renderPlain($preview_info);
              }
              break;
          }
        }
      }
    }
  }
}

/**
 * Get first 280 characters of text.
 *
 * @param string $text
 *   The text to check for.
 *
 * @return string
 *   The processed text.
 */
function _social_comment_get_summary($text) {
  $summary = html_entity_decode(strip_tags($text));
  $summary = preg_replace('/\n|\r|\t/m', ' ', $summary);
  $max_length = 280;

  if (mb_strlen($summary) > $max_length) {
    $summary = FieldPluginBase::trimText([
      'max_length' => $max_length,
      'word_boundary' => TRUE,
      'ellipsis' => TRUE,
    ], $summary);
  }

  return $summary;
}
