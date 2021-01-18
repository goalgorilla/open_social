<?php

/**
 * @file
 * Hooks provided by the Social Post module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\social_post\Entity\PostInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the icon and title for post visibility.
 *
 * @param string $visibility
 *   The current field_visibility value, "1" for 'Community' etc.
 * @param string $icon
 *   The icon name.
 * @param string $title
 *   The visibility label.
 *
 * @ingroup social_post_api
 */
function hook_social_post_visibility_info_alter($visibility, &$icon, &$title) {
  switch ($visibility) {
    case '5':
      $icon = 'community';
      $title = t('Community');
      break;

    case '6':
      $icon = 'lock';
      $title = t('Closed');
      break;
  }
}

/**
 * Alter the links of a post.
 *
 * @param array &$links
 *   A renderable array representing the post links.
 * @param \Drupal\social_post\Entity\PostInterface $entity
 *   The post being rendered.
 * @param array &$context
 *   Various aspects of the context in which the post links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the post is being viewed
 *   - 'langcode': the language in which the post is being viewed.
 *
 * @see \Drupal\social_post\PostViewBuilder::renderLinks()
 * @see \Drupal\social_post\PostViewBuilder::buildLinks()
 */
function hook_post_links_alter(array &$links, PostInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__post__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'post-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('post_test.report', ['post' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("post/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * Provide a method to alter a message about creating a new post.
 *
 * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
 *   The message.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\social_post\Form\PostForm::save()
 */
function hook_social_post_message_alter(TranslatableMarkup &$message, FormStateInterface $form_state) {
  $post = $form_state->getFormObject()->getEntity();

  if (mb_strlen($post->field_post->value) > 1000) {
    $message = t('Your long post has been posted.');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
