<?php

/**
 * @file
 * Contains post.page.inc..
 *
 * Page callback for Post entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;

/**
 * Prepares variables for Post templates.
 *
 * Default template: post.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_post(array &$variables) {
  // Fetch Post Entity Object.
  $post = $variables['elements']['#post'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Display Post created date in format 'time ago'.
  $created_time_ago = \Drupal::service('date.formatter')
    ->formatTimeDiffSince($post->getCreatedTime(), ['granularity' => 1, 'return_as_object' => TRUE]);
  $date = t('%time ago', ['%time' => $created_time_ago->getString()]);
  $variables['date'] = Link::fromTextAndUrl($date, $post->urlInfo('canonical'));

  // To change user picture settings (e.g. image style), edit the 'compact'
  // view mode on the User entity. Note that the 'compact' view mode might
  // not be configured, so remember to always check the theme setting first.
  $account = $post->getOwner();
  if ($account) {
    $variables['author_picture'] = \Drupal::service('social_profile.render_service')
      ->renderUserProfile($account, 'compact');
  }

  // Show a post has been edited.
  if ($post->getCreatedTime() != $post->getChangedTime()) {
    $variables['modified'] = TRUE;
  }

  // Visibility icon and label for template use.
  $variables['visibility_icon'] = social_post_get_visibility_details($post->get('field_visibility')->value, 'icon');
  $variables['visibility_label'] = social_post_get_visibility_details($post->get('field_visibility')->value, 'label');

  $variables['published'] = $post->isPublished();
}
