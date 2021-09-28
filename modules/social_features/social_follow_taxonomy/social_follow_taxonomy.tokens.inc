<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Follow taxonomy module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Implements hook_token_info().
 */
function social_follow_taxonomy_token_info() {

  $type = [
    'name' => t('Social Follow Taxonomy'),
    'description' => t('Tokens from the Social Follow Taxonomy module.'),
  ];

  $social_taxonomy['content_type'] = [
    'name' => t('The content type.'),
    'description' => t('The type of the content that is related to following term.'),
  ];

  $social_taxonomy['indefinite_article'] = [
    'name' => t('A/an article.'),
    'description' => t('Adds an article before the content label.'),
  ];

  $social_taxonomy['taxonomy_i_follow'] = [
    'name' => t('Taxonomy I follow.'),
    'description' => t('Taxonomy term I follow'),
  ];

  $social_taxonomy['post_url'] = [
    'name' => t('Post URL'),
    'description' => t('Post absolute URL.'),
  ];

  return [
    'types' => [
      'social_taxonomy' => $type,
    ],
    'tokens' => [
      'social_taxonomy' => $social_taxonomy,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_follow_taxonomy_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $display_name = '';

  if (empty($data['message'])) {
    return $replacements;
  }

  if ($type === 'social_taxonomy') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'content_type':
        case 'indefinite_article':
        case 'taxonomy_i_follow':
          /** @var \Drupal\message\Entity\Message $message */
          $message = $data['message'];
          // Get the related entity.
          if (!empty($message->get('field_message_related_object'))) {
            $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
            $target_id = $message->getFieldValue('field_message_related_object', 'target_id');
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {
              switch ($target_type) {
                case 'node':
                  /** @var \Drupal\node\Entity\Node $entity */
                  if ($entity instanceof NodeInterface) {
                    // Get the name of the content.
                    $display_name = strtolower($entity->getType());

                    // Get the names of terms related to the content.
                    $term_ids = social_follow_taxonomy_terms_list($entity);
                    $term_names = [];
                    foreach ($term_ids as $term_id) {
                      /** @var \Drupal\taxonomy\TermInterface $term */
                      $term = \Drupal::entityTypeManager()
                        ->getStorage('taxonomy_term')
                        ->load($term_id);
                      if ($term instanceof TermInterface) {
                        if (social_follow_taxonomy_term_followed($term)) {
                          $term_names[] = $term->getName();
                        }
                      }
                    }
                  }

                  break;

                case 'post':
                  // Get the name of the entity type.
                  $display_name = strtolower($entity->getEntityType()
                    ->getLabel());
                  break;
              }
            }
          }

          if ($name === 'content_type') {
            $replacements[$original] = $display_name;
          }

          if ($name === 'indefinite_article') {
            if (!empty($display_name)) {
              // Prepares a replacement token: content name.
              // When a name of content name starts from a vowel letter then
              // will be added "an" before this name. For example "an event".
              if (preg_match('/^[aeiou]/', $display_name)) {
                $indefinite_article = t('an');
              }
              else {
                $indefinite_article = t('a');
              }
              $replacements[$original] = $indefinite_article;
            }
            else {
              $replacements[$original] = '';
            }
          }

          if ($name === 'taxonomy_i_follow' && !empty($term_names)) {
            // Prepares a replacement token: a string with term names.
            // Wrap the names in quotation marks and separate it with commas.
            $replacement_string = "'" . implode("', '", $term_names) . "'";
            $replacements[$original] = $replacement_string;
          }

          break;
      }
    }
  }

  if ($type === 'message') {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      if ($name === 'post_url' && isset($message->field_message_related_object)) {
        $replacements[$original] = '';

        $target_type = $message->getFieldValue('field_message_related_object', 'target_type');
        $target_id = $message->getFieldValue('field_message_related_object', 'target_id');

        $entity = \Drupal::entityTypeManager()
          ->getStorage($target_type)
          ->load($target_id);

        if ($entity) {
          if ($target_type === 'post') {
            $post_link = Url::fromRoute('entity.post.canonical',
              ['post' => $entity->id()],
              ['absolute' => TRUE]
            )->toString();

            $replacements[$original] = $post_link;
          }
        }
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function social_follow_taxonomy_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'message' && !empty($context['data']['message'])) {
    /** @var Drupal\message\Entity\Message $message */
    $message = $context['data']['message'];

    // Alter the [message:cta_button] token, and [message:cta_button]
    // replace it with the rendered content with new text and link.
    if (isset($context['tokens']['cta_button']) || isset($context['tokens']['preview'])) {
      if ($message->hasField('field_message_related_object') && !$message->get('field_message_related_object')->isEmpty()) {
        /** @var \Drupal\social_activity\EmailTokenServices $email_token_services */
        $email_token_services = \Drupal::service('social_activity.email_token_services');

        if (!empty($entity = $email_token_services->getRelatedObject($message))) {
          /** @var \Drupal\node\Entity\Node $entity */
          $message_template_id = $message->getTemplate()->id();
          switch ($message_template_id) {
            case 'create_node_following_tag':
            case 'update_node_following_tag':
            case 'create_node_following_tag_stream':
              if (isset($context['tokens']['cta_button'])) {
                $link = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
                $cta_button = $email_token_services->getCtaButton($link, 'Read more about it');
                $replacements[$context['tokens']['cta_button']] = \Drupal::service('renderer')
                  ->renderPlain($cta_button);
              }

              // Replace the preview token.
              if (isset($context['tokens']['preview'])) {
                $preview_info = [];
                switch ($entity->getEntityTypeId()) {
                  case 'node':
                    /** @var \Drupal\node\Entity\Node $entity */
                    $preview_info = $email_token_services->getContentPreview($entity);
                    break;

                  case 'post':
                    /** @var \Drupal\social_post\Entity\Post */
                    $preview_info = $email_token_services->getContentPreview($entity);
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
