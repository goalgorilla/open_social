<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Group module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_token_info().
 */
function social_group_token_info() {

  $type = [
    'name' => t('Social Group'),
    'description' => t('Tokens from the social group module.'),
  ];

  $social_group['content_type'] = [
    'name' => t('The group content type.'),
    'description' => t('The type of the content that is created in the group.'),
  ];

  $social_group['content_url'] = [
    'name' => t('The group content url.'),
    'description' => t('The url to the content that is created in the group.'),
  ];

  $social_group['created_entity_link_html'] = [
    'name' => t('The (html) link to the created entity.'),
    'description' => t('The link to the created entity, can be post or node, will include raw HTML.'),
  ];

  return [
    'types' => ['social_group' => $type],
    'tokens' => [
      'social_group' => $social_group,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'social_group' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'content_type':
        case 'content_url':
        case 'created_entity_link_html':

          // Get the related entity.
          if (isset($message->field_message_related_object)) {
            $target_type = $message->field_message_related_object->target_type;
            $target_id = $message->field_message_related_object->target_id;
            $entity = \Drupal::entityTypeManager()
              ->getStorage($target_type)
              ->load($target_id);

            if (is_object($entity)) {
              switch ($target_type) {

                // If it's group content.
                case 'group_content':
                  /** @var \Drupal\group\Entity\GroupContent $entity */
                  $group_content_type = $entity->getGroupContentType();
                  if (!empty($group_content_type)) {
                    $display_name = $group_content_type->label();
                    $content_url = Url::fromRoute('entity.node.canonical',
                      ['node' => $entity->getEntity()->id()],
                      ['absolute' => TRUE]
                    );
                  }
                  break;

                // If it's node.
                case 'node':
                  $display_name = $entity->bundle();
                  break;

                // When it's a post or photo post.
                case 'photo':
                case 'post':
                  $display_name = mb_strtolower($entity->getEntityType()->getLabel());
                  $content_url = Url::fromRoute('entity.post.canonical',
                    ['post' => $entity->id()],
                    ['absolute' => TRUE]
                  );
                  break;

              }

              // When a name of content name starts from a vowel letter then
              // will be added "an" before this name. For example "an
              // event".
              if (isset($display_name)) {
                if (preg_match('/^[aeiou]/', $display_name)) {
                  $display_name = t('an @content_type', ['@content_type' => $display_name]);
                }
                else {
                  $display_name = t('a @content_type', ['@content_type' => $display_name]);
                }
              }

              if ($name === 'content_url') {
                if (isset($content_url)) {
                  $replacements[$original] = $content_url->toString();
                }
              }
              elseif ($name === 'content_type') {
                if (isset($display_name)) {
                  $replacements[$original] = $display_name;
                }
              }
              elseif ($name === 'created_entity_link_html') {
                // We should only use the label of entities who have a label.
                if ($link_label = $entity->label()) {
                  $entity_link_html = $display_name . ' <a href="' . $content_url->toString() . '">' . $link_label . '</a>';
                }
                else {
                  $entity_link_html = '<a href="' . $content_url->toString() . '">' . $display_name . '</a>';
                }

                $replacements[$original] = Markup::create($entity_link_html);
              }

            }
          }

          break;
      }
    }
  }

  return $replacements;
}
