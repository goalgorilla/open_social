<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Group Request module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function social_group_request_token_info() {

  $type = [
    'name' => t('Social Group Request'),
    'description' => t('Tokens from the social group request module.'),
  ];

  $social_group_request['request_page_link'] = [
    'name' => t('Request page link.'),
    'description' => t('The link to the request page of the group.'),
  ];

  return [
    'types' => ['social_group_request' => $type],
    'tokens' => [
      'social_group_request' => $social_group_request,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_group_request_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'social_group_request' && !empty($data['message'])) {
    /** @var \Drupal\message\MessageInterface $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'request_page_link':

          // Get the related entity.
          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;

            if (isset($target_type) && !empty($target_type) && $target_type === 'group_content') {
              /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
              $group_content = \Drupal::entityTypeManager()
                ->getStorage($target_type)
                ->load($target_id);

              if (!empty($group_content)) {
                $replacements[$original] = Url::fromRoute('view.group_pending_members.membership_requests', ['arg_0' => $group_content->getGroup()->id()])->toString();
              }
            }
          }

          break;
      }
    }
  }

  return $replacements;
}
