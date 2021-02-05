<?php

/**
 * @file
 * Builds placeholder replacement tokens for Social Like module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\votingapi\Entity\Vote;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info().
 */
function social_like_token_info() {

  $type = [
    'name' => t('Social Like'),
    'description' => t('Tokens from the social like module.'),
  ];

  $social_like['liked_entity'] = [
    'name' => t('URL of the liked entity.'),
    'description' => t('URL of the entity the like was created for'),
  ];

  $social_like['liked_content_type'] = [
    'name' => t('The liked content type.'),
    'description' => t('The type of the content that was liked'),
  ];

  $social_like['liked_entity_link_html'] = [
    'name' => t('The (html) link to the liked entity.'),
    'description' => t('The link to the entity, can be post or node, will include raw HTML.'),
  ];

  return [
    'types' => ['social_like' => $type],
    'tokens' => [
      'social_like' => $social_like,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function social_like_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'social_like' && !empty($data['message'])) {
    /** @var \Drupal\message\Entity\Message $message */
    $message = $data['message'];
    /** @var \Drupal\votingapi\Entity\Vote $vote */
    if ($vote = Vote::load($message->field_message_related_object->target_id)) {
      foreach ($tokens as $name => $original) {
        switch ($name) {

          case 'liked_entity':
          case 'liked_content_type':
          case 'liked_entity_link_html':

            $storage = \Drupal::entityTypeManager()->getStorage($vote->getVotedEntityType());
            $entity = $storage->load($vote->getVotedEntityId());
            $url_options = ['absolute' => TRUE];
            $link = $entity->toUrl('canonical', $url_options)->toString();
            if ($name === 'liked_entity') {
              $replacements[$original] = $link;
            }

            $content_type = $entity->getEntityTypeId();
            // Check if the content type is node.
            if ($content_type === 'node') {
              // Then get the bundle name.
              $content_type_label = mb_strtolower(\Drupal::entityTypeManager()
                ->getStorage('node_type')
                ->load($entity->bundle())
                ->label());
            }
            if ($content_type === 'post' || $content_type === 'photo' || $content_type === 'comment') {
              $content_type_label = mb_strtolower($entity->getEntityType()->getLabel());
            }
            if ($name === 'liked_content_type') {
              $replacements[$original] = $content_type_label;
            }

            if ($name === 'liked_entity_link_html') {
              // We should only use the label of entities who have a label.
              if ($content_type !== 'comment' && $link_label = $entity->label()) {
                $liked_entity_link_html = $content_type_label . ' <a href="' . $link . '">' . $link_label . '</a>';
              }
              else {
                $liked_entity_link_html = '<a href="' . $link . '">' . $content_type_label . '</a>';

                // Let's make an exception for comments.
                if ($content_type === 'comment') {
                  $commented_entity = $entity->getCommentedEntity();

                  $url_options = ['absolute' => TRUE];
                  /** @var \Drupal\Core\Entity\EntityBase $commented_entity */
                  $ref_link = $commented_entity->toUrl('canonical', $url_options)->toString();

                  // We should only use the label of entities who have a label.
                  if ($ref_link_label = $commented_entity->label()) {
                    if ($commented_entity instanceof Node) {
                      $commented_content_type = strtolower($commented_entity->type->entity->label());
                    }
                    else {
                      $commented_content_type = $commented_entity->bundle();
                    }
                    $liked_entity_link_html .= ' ' . t('on the') . ' ' . $commented_content_type . ' <a href="' . $ref_link . '">' . $ref_link_label . '</a>';
                  }
                  else {
                    $commented_content_type = mb_strtolower($commented_entity->getEntityType()->getLabel());
                    $liked_entity_link_html .= ' ' . t('on a') . ' <a href="' . $ref_link . '">' . $commented_content_type . '</a>';
                  }
                }
              }

              $replacements[$original] = Markup::create($liked_entity_link_html);
            }
            break;

        }
      }
    }
  }
  return $replacements;
}
