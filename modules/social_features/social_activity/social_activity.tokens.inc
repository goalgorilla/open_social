<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Activity module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function social_activity_token_info() {
  // The token additional information has been deprecated in 10.x.
  // It will be removed from 11.x.
  $tokens['additional_information'] = [
    'name' => t('Additional information.'),
    'description' => t('Additional message information. This token is deprecated, please use "preview" and "cta_button" tokens instead in your message templates.'),
  ];

  $tokens['preview'] = [
    'name' => t('Preview.'),
    'description' => t('Preview of the related entity.'),
  ];

  $tokens['cta_button'] = [
    'name' => t('CTA button'),
    'description' => t('A call to action button.'),
  ];

  return [
    'tokens' => [
      'message' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_activity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];
    if (!empty($message->get('field_message_related_object'))) {
      /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
      $email_token_services = \Drupal::service('social_activity.email_token_services');

      $entity = $email_token_services->getRelatedObject($message);
      if ($entity) {
        $date_formatter = \Drupal::service('date.formatter');
        $date_format = 'social_long_date';
        $summary = '';

        if ($entity->getEntityTypeId() === 'post') {
          /** @var \Drupal\social_post\Entity\Post $entity */

          // Prepare the link to post.
          $link = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();

          // Prepare the preview information.
          $preview_info = $email_token_services->getPostPreview($entity);

          // Prepare CTA button information.
          $cta_button = $email_token_services->getCtaButton($link, 'Leave a comment');

          // This is for token deprecated token 'additional_information'.
          $additional_information = [
            '#theme' => 'message_post_teaser',
            '#name' => $entity->getOwner()->getDisplayName(),
            '#date' => $date_formatter->format($entity->getCreatedTime(), $date_format),
            '#summary' => $summary,
            '#link' => $link,
          ];
        }
        elseif ($entity->getEntityTypeId() === 'group_content' && $entity instanceof GroupContentInterface) {
          /** @var \Drupal\node\Entity\Node $node */
          $node = $entity->getEntity();
          if ($node instanceof NodeInterface) {
            $preview_info = $email_token_services->getContentPreview($node);

            // Prepare the link to node.
            $link = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

            // Prepare the CTA button markup.
            $cta_button = $email_token_services->getCtaButton($link, 'Read more');

            // This is for token deprecated token 'additional_information'.
            $additional_information = [
              '#theme' => 'message_node_teaser',
              '#link' => $link,
              '#type' => strtoupper($node->getType()),
            ];
          }
        }

        // Replace tokens.
        foreach ($tokens as $name => $original) {
          switch ($name) {
            // The token 'additional_information'̊ is deprecated and
            // will be removed in 11.x release of Open Social.
            case 'additional_information':
              if (!empty($additional_information)) {
                $replacements[$original] = \Drupal::service('renderer')->renderPlain($additional_information);
              }
              break;

            case 'preview':
              if (!empty($preview_info)) {
                $replacements[$original] = \Drupal::service('renderer')->renderPlain($preview_info);
              }
              break;

            case 'cta_button':
              if (!empty($cta_button)) {
                $replacements[$original] = \Drupal::service('renderer')->renderPlain($cta_button);
              }
              break;
          }
        }
      }
    }
  }

  return $replacements;
}
