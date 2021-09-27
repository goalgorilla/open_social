<?php

/**
 * @file
 * Builds placeholder replacement tokens for message-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_tokens_alter().
 */
function social_private_message_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'message' && !empty($context['data']['message'])) {
    /** @var Drupal\message\Entity\Message $message */
    $message = $context['data']['message'];

    // Alter the [message:cta_button] or [message:preview] token, and
    // replace it with the rendered content with new text and link.
    if (isset($context['tokens']['cta_button']) || isset($context['tokens']['preview'])) {
      if ($message->hasField('field_message_related_object') && !$message->get('field_message_related_object')->isEmpty()) {

        /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
        $email_token_services = \Drupal::service('social_activity.email_token_services');

        if ($private_message = $email_token_services->getRelatedObject($message)) {
          /** @var \Drupal\private_message\Entity\PrivateMessage $private_message */
          $message_template_id = $message->getTemplate()->id();
          switch ($message_template_id) {
            case 'create_private_message':
              // Replace the preview token.
              if (isset($context['tokens']['cta_button'])) {
                // Use the mapper service to get the ID of the thread.
                /** @var \Drupal\private_message\Mapper\PrivateMessageMapper $pmService */
                $pmService = \Drupal::service('private_message.mapper');

                $thread_id = $pmService->getThreadIdFromMessage($private_message);

                // Build the url.
                $thread_url = Url::fromRoute('entity.private_message_thread.canonical',
                  ['private_message_thread' => $thread_id],
                  ['absolute' => TRUE]
                )->toString();
                $cta_button = $email_token_services->getCtaButton($thread_url, 'Reply to this message');
                if (!empty($cta_button)) {
                  $replacements[$context['tokens']['cta_button']] = \Drupal::service('renderer')->renderPlain($cta_button);
                }
              }

              // Replace the preview token.
              if (isset($context['tokens']['preview'])) {
                $preview_info = [
                  '#theme' => 'message_post_comment_preview',
                  '#summary' => $private_message->getMessage(),
                ];
                $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')->renderPlain($preview_info);
              }

              break;
          }
        }
      }
    }
  }
}
