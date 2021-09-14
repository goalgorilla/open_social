<?php

/**
 * @file
 * Contains activity.page.inc..
 *
 * Page callback for Activity entities.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
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
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityMalformedException
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
    ->formatTimeDiffSince($activity->getCreatedTime(), [
      'granularity' => 1,
      'return_as_object' => TRUE,
    ]);

  $date = t('@time ago', ['@time' => $created_time_ago->getString()]);
  if (isset($variables['elements']['#view_mode']) && in_array($variables['content']['field_activity_output_text']['#view_mode'], [
    'notification',
    'notification_archive',
  ])) {
    $variables['date'] = $date;
  }
  else {
    if ($full_url instanceof Url) {
      $variables['date'] = Link::fromTextAndUrl($date, $full_url)->toRenderable();
    }
  }
  $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();

  $entities = $activity->field_activity_entity->referencedEntities();
  if (!empty($entities)) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = reset($entities);

    if ($entity instanceof EntityInterface && $entity->getEntityTypeId() === 'group_content') {
      /** @var \Drupal\group\Entity\GroupContentInterface $entity */
      if ($entity->getGroupContentType()->getContentPluginId() === 'group_membership' && $entity->getEntity()->id() !== $activity->getOwnerId()) {
        $account = $entity->getEntity();
      }
    }
  }

  if (!isset($account)) {
    $account = $activity->getOwner();
  }

  // To change user picture settings (e.g. image style), edit the
  // 'compact_notification' view mode on the User entity. Note that the
  // 'compact_notification' view mode might not be configured, so remember to
  // always check the theme setting first.
  if ($account) {
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    if ($storage !== NULL) {
      $user_profile = $storage->loadByUser($account, 'profile');
      if ($user_profile) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($user_profile, 'compact_notification');
        if ($full_url === '') {
          $variables['actor'] = $content;
        }
        else {
          $variables['actor'] = Link::fromTextAndUrl($content, $account->toUrl());
        }
      }
    }

    // Our author is Anonymous. If that happens for a notification.
    // Lets provide the author as a default profile image.
    if ($variables['elements']['#view_mode'] === 'notification' && $account->isAnonymous()) {
      $default_image = social_profile_get_default_image();
      if (!empty($default_image['id'])) {
        $file = File::load($default_image['id']);
        $uri = $file->getFileUri();

        $variables['actor'] = [
          '#theme' => 'image_style',
          '#width' => $default_image['width'],
          '#height' => $default_image['height'],
          '#style_name' => 'social_medium',
          '#uri' => $uri,
        ];
      }
    }

  }

  $variables['full_url'] = $full_url;

  /** @var \Drupal\activity_creator\ActivityNotifications $activity_notification_service */
  $activity_notification_service = \Drupal::service('activity_creator.activity_notifications');
  // Get the notification status of the current user.
  $status = $activity_notification_service->getActivityStatus($activity, \Drupal::currentUser());
  // Add bg classes according to notification status.
  switch ($status) {
    case ACTIVITY_STATUS_RECEIVED:
      $status_class = 'bg-gray-lightest';

      if (\Drupal::routeMatch()->getRouteName() === 'view.activity_stream_notifications.page_1') {
        $activity_notification_service->changeStatusOfActivity([$activity->id()], \Drupal::currentUser(), ACTIVITY_STATUS_SEEN);

        // In case when a notification is unread then it should not be cached
        // because when a page will be opened the second time then a styling of
        // unread notification should be removed as the notification has been
        // marked as read.
        $variables['#cache']['max-age'] = 0;
      }

      break;

    default:
      $status_class = 'bg-white';
      break;
  }

  if (isset($status_class)) {
    $variables['status_class'] = $status_class;
  }

}
