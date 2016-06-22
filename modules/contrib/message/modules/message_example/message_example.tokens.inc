<?php

/**
 * @file
 * Place holder for the message example module.
 */

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;

/**
 * Implements hook_token_info().
 *
 * We need this part until the token module will be exported to Drupal 8.
 */
function message_example_token_info() {
  $type = [
    'name' => t('Message example'),
    'description' => t('Tokens for the message example module.'),
    'needs-data' => 'message',
  ];

  // User relate tokens.
  $message['user-name'] = [
    'name' => t("Author name"),
    'description' => t("The related author name."),
  ];

  $message['user-url'] = [
    'name' => t("Author url"),
    'description' => t("The related author URL."),
  ];

  // Comment relate tokens.
  $message['comment-url'] = [
    'name' => t("Comment url"),
    'description' => t("The comment url."),
  ];

  // Node related tokens.
  $message['node-render'] = [
    'name' => t("Rendered node"),
    'description' => t("The related node rendered to HTML."),
  ];

  $message['node-title'] = [
    'name' => t("Node title"),
    'description' => t("The related node title."),
  ];

  $message['node-url'] = [
    'name' => t("Node URL"),
    'description' => t("The related node URL."),
  ];

  return [
    'types' => ['message' => $type],
    'tokens' => [
      'message' => $message,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function message_example_tokens($type, $tokens, array $data = [], array $options = []) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = [];

  if ($type == 'message' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the comment.
        case 'user-name':
          $replacements[$original] = $message->getOwner()->label();
          break;

        case 'user-url':
          if ($message->getOwner()) {
            $replacements[$original] = $message->getOwner()->url();
          }
          break;

        case 'node-title':
          if ($message->bundle() == 'example_create_comment') {
            $replacements[$original] = $message->field_comment_reference->entity->getCommentedEntity()->label();
          }
          else {
            $replacements[$original] = $message->field_node_reference->get(0)->entity->label();
          }
          break;

        case 'node-url':
          if ($message->bundle() == 'example_create_comment') {
            $replacements[$original] = $message->field_comment_reference->entity->getCommentedEntity()->url();
          }
          else {
            $replacements[$original] = $message->field_node_reference->get(0)->entity->url();
          }
          break;

        case 'node-render':
          if ($message->bundle() == 'example_create_comment') {
            $entity = $message->field_comment_reference->entity->getCommentedEntity();
          }
          else {
            $entity = $message->field_node_reference->get(0)->entity;
          }

          $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
          $message_view = $view_builder->view($entity, 'teaser');
          $replacements[$original] = \Drupal::service('renderer')->renderRoot($message_view);
          break;

        case 'comment-url':
          $replacements[$original] = $message->field_comment_reference->entity->id();
          break;
      }
    }
  }

  return $replacements;
}
