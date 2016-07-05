<?php
/**
 * @file
 * ActivityNotifications.
 */

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Session\AccountInterface;


/**
 * Class ActivityNotifications to get Personalised activity items for account.
 *
 * @package Drupal\activity_creator
 */
class ActivityNotifications extends ControllerBase {

  /**
   * Returns the Notifications for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    Account object to get notifications for.
   * @param array $status
   *   Filter by status.
   *
   * @return array
   *   Return array of notification ids.
   */
  public function getNotifications(AccountInterface $account, $status = array(ACTIVITY_STATUS_RECEIVED)) {
    $ids = $this->getNotificationIds($account, $status);

    return $ids;
  }

  /**
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param array $status
   *   Status string: activity_creator_field_activity_status_allowed_values().
   *
   * @return array
   *   Return array of notifications as activity objects.
   */
  public function getNotificationsActivities(AccountInterface $account, $status = array(ACTIVITY_STATUS_RECEIVED)) {
    $ids = $this->getNotificationIds($account, $status);

    return entity_load_multiple('activity', $ids);
  }

  /**
   * Mark all notifications as Seen for account.
   *
   * @param AccountInterface $account
   *    Account object.
   *
   * @return int
   *    Number of remaining notifications.
   */
  public function markAllNotificationsAsSeen(AccountInterface $account) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, $status = array(ACTIVITY_STATUS_RECEIVED));

    foreach ($ids as $activity_id) {
      $activity = Activity::load($activity_id);
      $this->changeStatusOfActivity($activity, ACTIVITY_STATUS_SEEN);
    }

    $remaining_notifications = 0;
    return $remaining_notifications;
  }

  /**
   * Mark Notifications as Read for given account and entity..
   *
   * @param AccountInterface $account
   *    Account object.
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object.
   */
  public function markEntityNotificationsAsRead(AccountInterface $account, Entity $entity) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, array(ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN), $entity);

    foreach ($ids as $activity_id) {
      $activity = Activity::load($activity_id);
      $this->changeStatusOfActivity($activity, ACTIVITY_STATUS_READ);
    }

  }

  /**
   * Mark an entity as read for a given account.
   *
   * @param AccountInterface $account
   *    Account object.
   * @param \Drupal\Core\Entity\Entity $entity
   *    Entity object.
   */
  public function markEntityAsRead(AccountInterface $account, Entity $entity) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, $status = array(ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN), $entity);

    foreach ($ids as $activity_id) {
      $activity = Activity::load($activity_id);
      $this->changeStatusOfActivity($activity, ACTIVITY_STATUS_READ);
    }

  }

  /**
   * Change the status of an activity.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *    Activity object.
   * @param array $status
   *    See: activity_creator_field_activity_status_allowed_values().
   *
   * @return Activity
   *    Returns activity object.
   */
  public function changeStatusOfActivity(Activity $activity, $status = ACTIVITY_STATUS_RECEIVED) {
    $activity->set('field_activity_status', $status);

    return $activity->save();
  }

  /**
   * Returns the Activity ids for an account with destination 'notification'.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    Account object.
   * @param array $status
   *    Array of statuses.
   * @param \Drupal\Core\Entity\Entity $entity
   *    Optionally provide a related entity to get the activities for.
   *
   * @return array
   *    Returns an array of notification ids.
   */
  private function getNotificationIds(AccountInterface $account, $status = array(), Entity $entity = NULL) {
    $destinations = array('notifications');

    $uid = $account->id();

    $entity_query = \Drupal::entityQuery('activity');
    $entity_query->condition('field_activity_recipient_user', $uid, '=');
    $entity_query->condition('field_activity_destinations', $destinations, 'IN');

    if ($entity !== NULL) {
      $entity_type = $entity->getEntityTypeId();
      $entity_id = $entity->id();
      $entity_query->condition('field_activity_entity.target_id', $entity_id, '=');
      $entity_query->condition('field_activity_entity.target_type', $entity_type, '=');

    }
    if (!empty($status)) {
      $entity_query->condition('field_activity_status', $status, 'IN');
    }

    $ids = $entity_query->execute();

    return $ids;
  }

}
