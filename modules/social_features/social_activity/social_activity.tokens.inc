<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Activity module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

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

  if (in_array($type, ['message']) && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'additional_information':

          if (isset($message->field_message_related_object)) {
            $replacements[$original] = '';
            $summary = '';

            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if ($target_type == 'post') {
              $name = $entity->getOwner()->getDisplayName();

              $date_formatter = \Drupal::service('date.formatter');
              $date_format = 'social_long_date';
              $date = $date_formatter->format($entity->getCreatedTime(), $date_format);
              if (isset($entity->field_post) && !empty($entity->field_post->value)) {
                $summary = _social_comment_get_summary($entity->field_post->value);
              }

              $post_link = Url::fromRoute('entity.post.canonical',
                ['post' => $entity->id()],
                ['absolute' => TRUE]
              )->toString();

              $info = [
                '#theme' => 'message_post_teaser',
                '#name' => $name,
                '#date' => $date,
                '#summary' => $summary,
                '#link' => $post_link,
              ];

              $replacements[$original] = \Drupal::service('renderer')->render($info);
            }
            elseif ($target_type == 'group_content') {
              /** @var \Drupal\group\Entity\GroupContent $entity */
              $group_content_type = $entity->getGroupContentType();
              if (!empty($group_content_type)) {
                $display_name = Unicode::strtolower($group_content_type->label());
              }

              /* @var \Drupal\node\Entity\Node $node */
              $node = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->load($entity->entity_id->target_id);

              if ($node->getType() == 'topic' || $node->getType() == 'event') {
                $link = Url::fromRoute('entity.node.canonical',
                  ['node' => $node->id()],
                  ['absolute' => TRUE]
                )->toString();

                $info = [
                  '#theme' => 'message_node_teaser',
                  '#link' => $link,
                  '#type' => $display_name,
                ];

                $replacements[$original] = \Drupal::service('renderer')->render($info);
              }
            }
          }

          break;

      }
    }
  }

  return $replacements;
}
