<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Content Report module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\flag\Entity\Flagging;

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
    foreach ($tokens as $name => $original) {
      // Set the URL for the reported content.
      if ($name === 'content_url') {
        // Load the flag.
        $flagging_id = $data['message']->getFieldValue('field_message_related_object', 'target_id');
        $flagging = Flagging::load($flagging_id);

        // Load the flagged entity.
        $entity_storage = \Drupal::entityTypeManager()
          ->getStorage($flagging->getFlaggable()->getEntityTypeId());
        $entity = $entity_storage->load($flagging->getFlaggable()->id());

        // Set the URL for the entity.
        $replacements[$original] = $entity->toUrl()->setAbsolute()->toString();
      }
    }
  }

  return $replacements;
}
