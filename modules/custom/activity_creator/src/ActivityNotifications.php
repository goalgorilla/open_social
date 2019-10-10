<?php

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ActivityNotifications to get Personalised activity items for account.
 *
 * @package Drupal\activity_creator
 */
class ActivityNotifications extends ControllerBase {

  /**
   * Database services.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * ActivityNotifications constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database services.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Returns the Notifications for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object to get notifications for.
   * @param array $status
   *   Filter by status.
   *
   * @return array
   *   Return array of notification ids.
   */
  public function getNotifications(AccountInterface $account, array $status = [ACTIVITY_STATUS_RECEIVED]) {
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
  public function getNotificationsActivities(AccountInterface $account, array $status = [ACTIVITY_STATUS_RECEIVED]) {
    $ids = $this->getNotificationIds($account, $status);

    return entity_load_multiple('activity', $ids);
  }

  /**
   * Mark all notifications as Seen for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   *
   * @return int
   *   Number of remaining notifications.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markAllNotificationsAsSeen(AccountInterface $account) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED]);
    $this->changeStatusOfActivity($ids, ACTIVITY_STATUS_SEEN);

    return 0;
  }

  /**
   * Mark Notifications as Read for given account and entity..
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   */
  public function markEntityNotificationsAsRead(AccountInterface $account, EntityBase $entity) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN], $entity);

    $activities = Activity::loadMultiple($ids);
    foreach ($activities as $activity) {
      $this->changeStatusOfActivity($activity, ACTIVITY_STATUS_READ);
    }

  }

  /**
   * Mark an entity as read for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markEntityAsRead(AccountInterface $account, EntityBase $entity) {

    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN], $entity);
    $this->changeStatusOfActivity($ids, ACTIVITY_STATUS_READ);

  }

  /**
   * Change the status of an activity.
   *
   * @param array $ids
   *   Array of IDs.
   * @param int $status
   *   See: activity_creator_field_activity_status_allowed_values()
   */
  public function changeStatusOfActivity(array $ids, $status = ACTIVITY_STATUS_RECEIVED) {
    $this->database->update('activity_notification_status')
      ->fields([
        'status' => $status,
      ])
      ->condition('aid', $ids, 'IN')
      ->execute();
  }

  /**
   * Returns the Activity ids for an account with destination 'notification'.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param array $status
   *   Array of statuses.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Optionally provide a related entity to get the activities for.
   *
   * @return array
   *   Returns an array of notification ids.
   */
  private function getNotificationIds(AccountInterface $account, array $status = [], EntityBase $entity = NULL) {
    $destinations = ['notifications'];

    $uid = $account->id();

    $query = $this->database->select('activity_notification_status', 'ans')
      ->fields('ans', ['aid'])
      ->condition('uid', $uid);

    if (!empty($status)) {
      $query->condition('status', $status, 'IN');
    }
    $ids = $query->execute()->fetchCol();

    return $ids;
  }

}
