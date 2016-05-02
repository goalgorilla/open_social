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

  // Display comment created date in format 'time ago'.
  $created_time_ago = \Drupal::service('date.formatter')
    ->formatTimeDiffSince($post->getCreatedTime(), array('granularity' => 2));
  $date = t('%time ago', array('%time' => $created_time_ago));
  $variables['date'] = Link::fromTextAndUrl($date, $post->urlInfo('canonical'));

  // To change user picture settings (e.g. image style), edit the 'compact'
  // view mode on the User entity. Note that the 'compact' view mode might
  // not be configured, so remember to always check the theme setting first.
  $account = $post->getOwner();
  if ($account) {
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    if (!empty($storage)) {
      $user_profile = $storage->loadByUser($account, 'profile');
      if ($user_profile) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($user_profile, 'compact');
        $variables['author_picture'] = $content;
      }
    }
  }
}
