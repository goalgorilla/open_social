<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Mentions module.
 */

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\mentions\Entity\Mentions;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\social_post\Entity\Post;

/**
 * Implements hook_token_info().
 */
function social_mentions_token_info() {
  $type = [
    'name' => t('Social Mentions'),
    'description' => t('Tokens from the social mentions module.'),
    'needs-data' => 'profile',
  ];

  $social_mentions['mentioned_user'] = [
    'name' => t('Get the mentioned user'),
    'description' => t('Display the mentioned user in a post'),
  ];

  $social_mentions['user_name'] = [
    'name' => t('User name'),
    'description' => t('First and last name or username, depends on settings.'),
  ];

  $social_mentions['commented_entity_link_html'] = [
    'name' => t('The (html) link to the commented entity.'),
    'description' => t('The link to the commented entity, can be post or node, will include raw HTML.'),
  ];

  return [
    'types' => ['social_mentions' => $type],
    'tokens' => [
      'social_mentions' => $social_mentions,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_mentions_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_mentions' && !empty($data['profile'])) {
    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = $data['profile'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'user_name':

          $config = \Drupal::config('mentions.settings');
          switch ($config->get('suggestions_format')) {
            case SOCIAL_PROFILE_SUGGESTIONS_FULL_NAME:
            case SOCIAL_PROFILE_SUGGESTIONS_ALL:
              $user_name = $profile->getOwner()->getDisplayName();
          }
          if (empty($user_name)) {
            $user_name = $profile->getOwner()->getAccountName();
          }

          $replacements[$original] = $user_name;
          break;

      }
    }
  }

  if ($type == 'social_mentions' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    if ($message instanceof Message) {
      foreach ($tokens as $name => $original) {
        switch ($name) {

          case 'mentioned_user':

            if ($name === 'mentioned_user') {
              if (!$message->get('field_message_related_object')->isEmpty()) {
                $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
                $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
                $mention = \Drupal::entityTypeManager()
                  ->getStorage($target_type)
                  ->load($target_id);

                if ($mention->getEntityTypeId() === 'mentions') {
                  $loadUserId = User::load($mention->getMentionedUserID());
                  $user = $loadUserId->getDisplayName();

                  $replacements[$original] = $user;
                }
              }
            }

            break;

          case 'commented_entity_link_html':
          case 'commented_entity_link_html:capitalized':

            if (!$message->get('field_message_related_object')->isEmpty()) {
              $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
              $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
              /** @var \Drupal\mentions\Entity\Mentions $mention */
              $mention = \Drupal::entityTypeManager()
                ->getStorage($target_type)
                ->load($target_id);

              if ($mention instanceof Mentions && $mentioned_entity = $mention->getMentionedEntity()) {
                if ($mentioned_entity->getEntityTypeId() === 'comment' && $mentioned_entity instanceof CommentInterface) {
                  $entity = $mentioned_entity->getCommentedEntity();
                }
                else {
                  $entity = $mentioned_entity;
                }
              }
            }

            if (isset($entity)) {
              switch ($entity->getEntityTypeId()) {
                case 'node':
                  // Then get the bundle name.
                  $content_type_label = '';
                  $label = \Drupal::entityTypeManager()->getStorage('node_type')->load($entity->bundle())->label();
                  if (is_string($label) || $label instanceof TranslatableMarkup) {
                    $content_type_label = mb_strtolower($label);
                  }
                  break;

                case 'post':
                case 'photo':
                case 'comment':
                  $content_type_label = mb_strtolower($entity->getEntityType()->getLabel());
                  break;
              }

              if (isset($content_type_label) && str_ends_with($name, ':capitalized')) {
                $content_type_label = ucfirst($content_type_label);
              }

              $url_options = ['absolute' => TRUE];
              $link = $entity->toUrl('canonical', $url_options)->toString();

              // We should only use the label of entities who have a label.
              if ($link_label = $entity->label()) {
                $entity_link_html = $content_type_label . ' <a href="' . $link . '">' . $link_label . '</a>';
              }
              else {
                $entity_link_html = '<a href="' . $link . '">' . $content_type_label . '</a>';
              }

              $replacements[$original] = Markup::create($entity_link_html);
            }

            break;
        }
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function social_mentions_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'message' && !empty($context['data']['message'])) {
    // Translate hook_tokens options to the options for TranslatableMarkup.
    $translation_options = [
      'langcode' => $context['options']['langcode'] ?? NULL,
    ];

    /** @var Drupal\message\Entity\Message $message */
    $message = $context['data']['message'];

    // Alter the [message:cta_button] or [message:preview] token,
    // and replace it with the rendered content with new text and link.
    if (isset($context['tokens']['cta_button']) || isset($context['tokens']['preview'])) {
      if (isset($message->field_message_related_object)) {
        $message_template_id = $message->getTemplate()->id();
        switch ($message_template_id) {
          case 'create_mention_comment':
          case 'create_mention_post':
          case 'create_comment_reply_mention':
            /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
            $email_token_services = \Drupal::service('social_activity.email_token_services');
            $mention = $email_token_services->getRelatedObject($message);

            if ($mention instanceof Mentions && $mentioned_entity = $mention->getMentionedEntity()) {
              if ($mentioned_entity->getEntityTypeId() === 'comment') {
                /** @var \Drupal\comment\Entity\Comment $mentioned_entity */
                $entity = $mentioned_entity->getCommentedEntity();
              }
              else {
                $entity = $mentioned_entity;
              }
            }
            // Related object can be already a comment entity.
            if ($mention instanceof CommentInterface) {
              /** @var Drupal\comment\Entity\Comment $entity */
              $entity = $mention;
            }
            if (isset($entity)) {
              // Prepare comment field name.
              $comment_field_name = '';
              $entity_id = $entity->getEntityTypeId();
              switch ($entity_id) {
                case 'comment':
                  $comment_field_name = 'field_comment_body';
                  break;

                case 'node':
                  $comment_field_name = 'field_' . $entity->bundle() . '_comments';
                  break;

                case 'post':
                case 'photo':
                  // Get the summary of the comment.
                  $comment_field_name = 'field_post_comments';
                  break;
              }

              // Get the comment entity.
              $comment = NULL;
              if ($comment_field_name && $entity instanceof ContentEntityBase && $entity->hasField($comment_field_name)) {
                $comment_id = $entity->getFieldValue($comment_field_name, 'cid');
                if ($comment_id) {
                  /** @var Drupal\comment\Entity\Comment $comment */
                  $comment = \Drupal::entityTypeManager()->getStorage('comment')
                    ->load($comment_id);
                }
                // Check if the current entity already is a comment.
                if (!$comment && $entity instanceof CommentInterface) {
                  /** @var Drupal\comment\Entity\Comment $comment */
                  $comment = $entity;
                }
              }

              // Prepare the CTA button.
              if (isset($context['tokens']['cta_button'])) {
                $options = ['absolute' => TRUE];
                // Append the comment reference in link.
                if ($comment instanceof CommentInterface) {
                  $options['fragment'] = 'comment-' . $comment->id();
                }

                $link = $entity->toUrl('canonical', $options);
                $cta_button = $email_token_services->getCtaButton($link, new TranslatableMarkup('Reply to this comment', [], $translation_options));
                $replacements[$context['tokens']['cta_button']] = \Drupal::service('renderer')
                  ->renderInIsolation($cta_button);
              }

              // Prepare the comment preview.
              if (isset($context['tokens']['preview'])) {
                $preview_info = [];
                switch ($entity_id) {
                  // If the mention was made in a comment on a comment
                  // or a comment on node.
                  case 'comment':
                  case 'node':
                    // Get the summary of the comment.
                    if ($comment instanceof CommentInterface) {
                      $preview_info = $email_token_services->getCommentPreview($comment);
                    }
                    break;

                  // If the mention was made in a post itself.
                  case 'post':
                  case 'photo':
                    // Get the summary of the post.
                    if ($entity instanceof Post) {
                      $preview_info = $email_token_services->getPostPreview($entity);
                    }
                    break;
                }
                $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')
                  ->renderInIsolation($preview_info);
              }
            }
            break;
        }
      }
    }
  }
}
