<?php
/**
 * ActivityNotifications
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
   * Returns the Activity ids with destination 'notification' for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
   * @return array
   */
  public function getNotifications(AccountInterface $account, $status = array(ACTIVITY_STATUS_RECEIVED)) {
    $ids = $this->getNotificationIds($account, $status);

    return $ids;
  }

  /**
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
   * @return array
   */
  public function getNotificationsActivities(AccountInterface $account, $status = array(ACTIVITY_STATUS_RECEIVED)) {
    $ids = $this->getNotificationIds($account, $status);

    return entity_load_multiple('activity', $ids);
  }


  /**
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
   * @return array
   */
  public function getNotificationsForEntity(AccountInterface $account, $status = ACTIVITY_STATUS_RECEIVED) {
    $activity->set('field_activity_status', $status);

    return $activity->save();
  }

  /**
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
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
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
   * @return array
   */
  public function changeStatusOfActivity(Activity $activity, $status = ACTIVITY_STATUS_RECEIVED) {
    $activity->set('field_activity_status', $status);

    return $activity->save();
  }

  /**
   * Returns the Activity objects with destination 'notification' for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param array $status
   *   see: activity_creator_field_activity_status_allowed_values()
   * @return array
   */
  private function getNotificationIds(AccountInterface $account, $status = array(ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN, ACTIVITY_STATUS_READ), Entity $entity = NULL) {
    $destinations = array('notifications');

    $uid = $account->id();

    // TODO use dependency injection for entity query?
    $entity_query = \Drupal::entityQuery('activity');
    $entity_query->condition('field_activity_recipient_user', $uid, '=');
    $entity_query->condition('field_activity_destinations', $destinations, 'IN');

    if ($entity !== NULL) {
      $entity_type = $entity->getEntityTypeId();
      $entity_id = $entity->id();
      $entity_query->condition('field_activity_entity.target_id', $entity_id, '=');
      $entity_query->condition('field_activity_entity.target_type', $entity_type, '=');

    }
    $entity_query->condition('field_activity_status', $status, 'IN');

    $ids = $entity_query->execute();

    return $ids;
  }

}