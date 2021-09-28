<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Content Report module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_post\Entity\PostInterface;

/**
 * Implements hook_token_info().
 */
function social_content_report_token_info() {
  $tokens['content_url'] = [
    'name' => t('URL of the reported content.'),
    'description' => t('URL of the content that was reported.'),
  ];

  return [
    'types' => [
      'social_content_report' => [
        'name' => t('Social Content Report'),
        'description' => t('Tokens from the Social Content Report module.'),
      ],
    ],
    'tokens' => [
      'social_content_report' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_content_report_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'social_content_report' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    $storage = \Drupal::entityTypeManager()->getStorage('flagging');

    foreach ($tokens as $name => $original) {
      // Set the URL for the reported content.
      if ($name === 'content_url' && $message->getFieldValue('field_message_related_object', 'target_type') === 'flagging') {
        // Load the flag.
        $flagging_id = $message->getFieldValue('field_message_related_object', 'target_id');

        /** @var \Drupal\flag\FlaggingInterface $flagging */
        $flagging = $storage->load($flagging_id);

        // Load the flagged entity.
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $flagging->getFlaggable();

        // Set the URL for the entity.
        $replacements[$original] = $entity->toUrl()->setAbsolute()->toString();
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function social_content_report_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'message' && !empty($context['data']['message'])) {
    /** @var Drupal\message\Entity\Message $message */
    $message = $context['data']['message'];

    // Alter the [message:cta_button] or [message:preview] token, and
    // replace it with the rendered content with new text and link.
    if (isset($context['tokens']['cta_button']) || isset($context['tokens']['preview'])) {
      if ($message->hasField('field_message_related_object') && !$message->get('field_message_related_object')->isEmpty()) {
        $target_type = $message->getFieldValue('field_message_related_object', 'target_type');

        /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
        $email_token_services = \Drupal::service('social_activity.email_token_services');

        if ($target_type === 'flagging') {
          $message_template_id = $message->getTemplate()->id();
          switch ($message_template_id) {
            case 'content_reported':
              // Load the flag.
              $flagging_id = $message->getFieldValue('field_message_related_object', 'target_id');
              $storage = \Drupal::entityTypeManager()->getStorage('flagging');

              // Replace the preview token.
              if (isset($context['tokens']['cta_button'])) {
                $link = Url::fromRoute('view.report_overview.overview')->toString();
                $cta_button = $email_token_services->getCtaButton($link, 'Check the reported contents');
                $replacements[$context['tokens']['cta_button']] = \Drupal::service('renderer')->renderPlain($cta_button);
              }

              // Replace the preview token.
              if (isset($context['tokens']['preview'])) {
                /** @var \Drupal\flag\FlaggingInterface $flagging */
                $flagging = $storage->load($flagging_id);

                // Load the flagged entity.
                $entity = $flagging->getFlaggable();
                $entity_type = $entity->getEntityTypeId();

                $summary = '';
                $preview_info = [];

                switch ($entity_type) {
                  case 'post':
                    if ($entity instanceof PostInterface) {
                      /** @var \Drupal\social_post\Entity\Post $entity */
                      $preview_info = $email_token_services->getPostPreview($entity);
                      $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')->renderPlain($preview_info);
                    }
                    break;

                  case 'node':
                    if ($entity instanceof NodeInterface) {
                      /** @var \Drupal\node\Entity\Node $entity */
                      $preview_info = $email_token_services->getContentPreview($entity);
                      $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')->renderPlain($preview_info);
                    }
                    break;

                  case 'comment':
                    /** @var \Drupal\comment\Entity\Comment $entity */
                    $preview_info = $email_token_services->getCommentPreview($entity);
                    $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')->renderPlain($preview_info);
                    break;
                }
                $replacements[$context['tokens']['preview']] = \Drupal::service('renderer')
                  ->renderPlain($preview_info);
              }
              break;
          }
        }
      }
    }
  }
}
