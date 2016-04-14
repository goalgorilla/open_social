<?php

/**
 * @file
 * Contains post.page.inc..
 *
 * Page callback for Post entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

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

  // Display comment created date in format 'time ago'.
  $created_time_ago = \Drupal::service('date.formatter')
    ->formatTimeDiffSince($post->getCreatedTime(), array('granularity' => 2));
  $variables['date'] = t('%time ago', array('%time' => $created_time_ago));

  $variables['display_submitted'] = TRUE;
  if ($variables['display_submitted']) {
    if (theme_get_setting('features.node_user_picture')) {
      // To change user picture settings (e.g. image style), edit the 'compact'
      // view mode on the User entity. Note that the 'compact' view mode might
      // not be configured, so remember to always check the theme setting first.
      $variables['author_picture'] = user_view($post->getOwner(), 'compact');
    }
  }

}
