<?php

/**
 * @file
 * Hooks provided by the Social Post module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the icon and title for post visibility.
 *
 * @param string $visibility
 *   The current field_visibility value, "1" for 'Community' etc.
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
 * @param \Drupal\social_post\PostInterface $entity
 *   The post being rendered.
 * @param array &$context
 *   Various aspects of the context in which the post links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the post is being viewed
 *   - 'langcode': the language in which the post is being viewed
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
 * @} End of "addtogroup hooks".
 */
