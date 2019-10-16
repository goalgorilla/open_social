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
  public function getNotifications(AccountInterface $account, array $status = [ACTIVITY_STATUS_RECEIVED]): array {
    return $this->getNotificationIds($account, $status);
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNotificationsActivities(AccountInterface $account, array $status = [ACTIVITY_STATUS_RECEIVED]): array {
    if (!empty($ids = $this->getNotificationIds($account, $status))) {
      return $this->entityTypeManager()->getStorage('activity')->loadMultiple($ids);
    }

    // Log as notice.
    $this->loggerFactory->get('activity_creator')->notice('There are no activity notifications for user @id', [
      '@id' => $account->id(),
    ]);
  }

  /**
   * Mark all notifications as Seen for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   *
   * @return bool
   *   TRUE or FALSE depending upon update status.
   */
  public function markAllNotificationsAsSeen(AccountInterface $account): bool {
    // Retrieve all the activities referring this entity for this account.
    if (!empty($ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED]))) {
      return $this->changeStatusOfActivity($ids, $account, ACTIVITY_STATUS_SEEN);
    }

    // Log as notice.
    $this->loggerFactory->get('activity_creator')->notice('There are no notification to be marked as seen for user @id', [
      '@id' => $account->id(),
    ]);
  }

  /**
   * Mark Notifications as Read for given account and entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   *
   * @return bool
   *   TRUE or FALSE depending upon update status.
   */
  public function markEntityNotificationsAsRead(AccountInterface $account, EntityBase $entity) {
    try {
      // Retrieve all the activities referring to this entity for this account.
      if ($entity !== NULL) {
        $entity_type = $entity->getEntityTypeId();
        $entity_id = $entity->id();
        $entity_query = $this->entityTypeManager()->getStorage('activity')->getQuery();
        $entity_query->condition('field_activity_recipient_user', $account->id(), '=');
        $entity_query->condition('field_activity_destinations', ['notifications'], 'IN');
        $entity_query->condition('field_activity_entity.target_id', $entity_id, '=');
        $entity_query->condition('field_activity_entity.target_type', $entity_type, '=');
        if (!empty($ids = $entity_query->execute())) {
          // Change the status of this entity.
          return $this->changeStatusOfActivity($ids, $account, ACTIVITY_STATUS_READ);
        }
      }
      else {
        // Log as notice.
        $this->loggerFactory->get('activity_creator')->notice('There are no notification to be marked as read for user @id', [
          '@id' => $account->id()
        ]);
      }
    }
    catch (\Exception $exception) {
      $this->loggerFactory->get('activity_creator')->error($exception->getMessage());
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
   * @deprecated in opensocial:8.x-7.1 and is removed from opensocial:8.x-8.0. Use
   *   \Drupal\activity_creator\ActivityNotifications
   * ::markEntityNotificationsAsRead() instead.
   *
   * TODO: Change @see to point to a change record.
   * @see https://www.drupal.org/project/social/issues/3087083
   */
  public function markEntityAsRead(AccountInterface $account, EntityBase $entity) {
    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN], $entity);
    $this->changeStatusOfActivity($ids, $account, ACTIVITY_STATUS_READ);

  }

  /**
   * Mark an activity notification as read for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *   Activity object.
   *
   * @return bool
   *   TRUE or FALSE depending upon update status.
   */
  public function markNotificationAsRead(AccountInterface $account, Activity $activity): bool {
    // Change the activity notification status to read.
    return $this->changeStatusOfActivity([$activity->id()], $account, ACTIVITY_STATUS_READ);

  }

  /**
   * Mark all acitivty notification as read for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   *
   * @return bool
   *   TRUE or FALSE depending upon update status.
   */
  public function markAllNotificationAsRead(AccountInterface $account) {
    // Retrieve all the activities referring this entity for this account.
    if (!empty($ids = $this->getNotificationIds($account, [ACTIVITY_STATUS_RECEIVED, ACTIVITY_STATUS_SEEN]))) {
      return $this->changeStatusOfActivity($ids, $account, ACTIVITY_STATUS_READ);
    }

    $this->loggerFactory->get('activity_creator')->notice('There are no notification to be marked as read for user @id', [
      '@id' => $account->id(),
    ]);
  }

  /**
   * Change the status of an activity.
   *
   * @param array $ids
   *   Array of Activity entity IDs.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param int $status
   *   See: activity_creator_field_activity_status_allowed_values()
   *
   * @return bool
   *   Status of update query.
   */
  protected function changeStatusOfActivity(array $ids, AccountInterface $account, $status = ACTIVITY_STATUS_RECEIVED): bool {
    if (!empty($ids)) {
      // The transaction opens here.
      $txn = $this->database->startTransaction();
      try {
        // Collect the information about affected rows.
        $count = $this->database->update('activity_notification_status')
          ->fields(['status' => $status])
          ->condition('uid', $account->id())
          ->condition('aid', $ids, 'IN')
          ->execute();
        return TRUE;
      }
      catch (\Exception $exception) {
        // Something went wrong somewhere, so roll back now.
        $txn->rollBack();
        // Log the exception to watchdog.
        $this->loggerFactory->get('activity_creator')->error($exception->getMessage());
      }
    }

    return FALSE;
  }

  /**
   * Returns the Activity ids for an account with destination 'notification'.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param array $status
   *   Array of notification statuses.
   *
   * @return array
   *   Returns an array of notification ids or empty array.
   */
  protected function getNotificationIds(AccountInterface $account, array $status = []): array {
    // Get the user ID.
    if (!empty($uid = $account->id())) {
      try {
        $query = $this->database->select('activity_notification_status', 'ans')
          ->fields('ans', ['aid'])
          ->condition('uid', $uid);

        if (!empty($status)) {
          $query->condition('status', $status, 'IN');
        }
        return $query->execute()->fetchCol();
      }
      catch (\Exception $exception) {
        // Log the exception to watchdog.
        $this->loggerFactory->get('activity_creator')->error($exception->getMessage());
      }
    }
    else {
      return [];
    }
  }

}
