<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Mentions module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\mentions\Entity\Mentions;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;

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

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'mentioned_user':

          if ($name === 'mentioned_user') {
            if (isset($message->field_message_related_object)) {
              $target_type = $message->field_message_related_object->target_type;
              $target_id = $message->field_message_related_object->target_id;
              $mention = \Drupal::entityTypeManager()
                ->getStorage($target_type)
                ->load($target_id);

              if ($mention->getEntityTypeId() == 'mentions') {
                $loadUserId = User::load($mention->getMentionedUserID());
                $user = $loadUserId->getDisplayName();

                $replacements[$original] = $user;
              }
            }
          }

          break;

        case 'commented_entity_link_html':

          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            /** @var \Drupal\mentions\Entity\Mentions $mention */
            $mention = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if ($mention instanceof Mentions && $mentioned_entity = $mention->getMentionedEntity()) {
              if ($mentioned_entity->getEntityTypeId() === 'comment') {
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
                $content_type_label = mb_strtolower(\Drupal::entityTypeManager()
                  ->getStorage('node_type')
                  ->load($entity->bundle())
                  ->label());
                break;

              case 'post':
              case 'photo':
              case 'comment':
                $content_type_label = mb_strtolower($entity->getEntityType()->getLabel());
                break;

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

  return $replacements;
}
