<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Activity module.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function social_activity_token_info() {
  $additional_information['additional_information'] = [
    'name' => t('Additional information.'),
    'description' => t('Additional message information.'),
  ];

  return [
    'tokens' => [
      'message' => $additional_information,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_activity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\MessageInterface $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'additional_information':

          if (isset($message->field_message_related_object)) {
            $replacements[$original] = '';
            $summary = '';

            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;

            $entity = \Drupal::entityTypeManager()->getStorage($target_type)
              ->load($target_id);

            if ($entity) {
              if ($target_type === 'post') {
                /** @var \Drupal\social_post\Entity\PostInterface $entity */
                $name = $entity->getOwner()->getDisplayName();

                $date_formatter = \Drupal::service('date.formatter');
                $date_format = 'social_long_date';
                $date = $date_formatter->format($entity->getCreatedTime(), $date_format);

                if ($entity->hasField('field_post') && !$entity->field_post->isEmpty()) {
                  $summary = _social_comment_get_summary($entity->field_post->value);
                }

                $post_link = Url::fromRoute('entity.post.canonical',
                  ['post' => $entity->id()],
                  ['absolute' => TRUE]
                )->toString();

                $string_parts = preg_split("/(\[\~\d+\])/", $summary, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                $summary = "";
                foreach ($string_parts as $string_part) {
                  if (strpos($string_part, '[~') !== FALSE
                    && strpos($string_part, ']') !== FALSE) {
                    $profile_id = substr($string_part, 2, -1);
                    $query = Database::getConnection()->select('profile', 'p');
                    $query->fields('p', ['uid']);
                    $query->condition('profile_id', $profile_id);
                    $user_id = $query->execute()->fetchField();
                    if (!empty($user_id)) {
                      $user = User::load($user_id);
                      $mention = $user->getDisplayName();
                      $summary .= $mention;
                    }
                  }
                  else {
                    $summary .= $string_part;
                  }
                }

                $info = [
                  '#theme' => 'message_post_teaser',
                  '#name' => $name,
                  '#date' => $date,
                  '#summary' => $summary,
                  '#link' => $post_link,
                ];

                $replacements[$original] = \Drupal::service('renderer')->renderPlain($info);
              }
              elseif ($target_type === 'group_content') {
                /** @var \Drupal\group\Entity\GroupContentInterface $entity */
                $group_content_type = $entity->getGroupContentType();

                if ($group_content_type !== NULL) {
                  $display_name = mb_strtolower($group_content_type->label());
                }

                /* @var \Drupal\node\NodeInterface $node */
                $node = $entity->getEntity();

                if (in_array($node->getType(), ['topic', 'event'])) {
                  $link = Url::fromRoute('entity.node.canonical',
                    ['node' => $node->id()],
                    ['absolute' => TRUE]
                  )->toString();

                  $info = [
                    '#theme' => 'message_node_teaser',
                    '#link' => $link,
                    '#type' => $display_name,
                  ];

                  $replacements[$original] = \Drupal::service('renderer')->renderPlain($info);
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
