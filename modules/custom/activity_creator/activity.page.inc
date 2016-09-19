<?php

/**
 * @file
 * Contains activity.page.inc..
 *
 * Page callback for Activity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;

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
  /** @var \Drupal\activity_creator\Entity\Activity $activity */
  $activity = $variables['elements']['#activity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Get the url to the related entity.
  $full_url = $activity->getRelatedEntityUrl();

  // Display activity created date in format 'time ago'.
  $created_time_ago = \Drupal::service('date.formatter')
    ->formatTimeDiffSince($activity->getCreatedTime(), array('granularity' => 1, 'return_as_object' => TRUE));
  $date = t('%time ago', array('%time' => $created_time_ago->getString()));
  if ($full_url == '') {
    $variables['date'] = $date;
  }
  else {
    $variables['date'] = Link::fromTextAndUrl($date, $full_url);
  }
  $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();

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
        if ($full_url == '') {
          $variables['actor'] = $content;
        }
        else {
          $variables['actor'] = Link::fromTextAndUrl($content, $full_url);
        }
      }
    }
  }

  $variables['full_url'] = $full_url;

  $status = $activity->get('field_activity_status')->getValue();

  switch ($status['0']['value']) {
    case ACTIVITY_STATUS_READ:
      $status_class = 'bg-white';
      break;

    default:
      $status_class = 'bg-gray-lightest';
      break;
  }

  if (isset($status_class)) {
    $variables['status_class'] = $status_class;
  }

}
