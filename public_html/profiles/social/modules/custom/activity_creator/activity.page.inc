<?php

/**
 * @file
 * Contains activity.page.inc..
 *
 * Page callback for Activity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Activity templates.
 *
 * Default template: activity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_activity(array &$variables) {
  // Fetch Activity Entity Object.
  $activity = $variables['elements']['#activity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Display comment created date in format 'time ago'.
  $created_time_ago = \Drupal::service('date.formatter')
    ->formatTimeDiffSince($activity->getCreatedTime(), array('granularity' => 2));
  $date = t('%time ago', array('%time' => $created_time_ago));
  $variables['date'] = $date;

  // To change user picture settings (e.g. image style), edit the 'compact'
  // view mode on the User entity. Note that the 'compact' view mode might
  // not be configured, so remember to always check the theme setting first.
  $account = $activity->getOwner();
  if ($account) {
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    if (!empty($storage)) {
      $user_profile = $storage->loadByUser($account, 'profile');
      if ($user_profile) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($user_profile, 'compact');
        $variables['actor'] = $content;
      }
    }
  }

  $full_url = $activity->getRelatedEntityUrl();
  $variables['full_url'] = $full_url;
}
