<?php

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
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
   *   Return array of notifications as activity objects or an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNotificationsActivities(AccountInterface $account, array $status = [ACTIVITY_STATUS_RECEIVED]): array {
    if (!empty($ids = $this->getNotificationIds($account, $status))) {
      return $this->entityTypeManager()->getStorage('activity')->loadMultiple($ids);
    }

    return [];
  }

  /**
   * Gets all activity IDs by given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   Return array of activity IDs or an empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getActivityIdsByEntity(EntityInterface $entity): array {
    $ids = [];
    $entity_id = $entity->id();
    $entity_type = $entity->getEntityTypeId();
    switch ($entity_type) {
      case 'user':
      case 'group':
        $entity_query = $this->entityTypeManager()->getStorage('activity')->getQuery();
        $entity_query->condition('field_activity_recipient_' . $entity_type, $entity_id, '=');
        $ids = $entity_query->execute();
        break;

      case 'group_content':
        /** @var \Drupal\group\Entity\GroupContent $entity */
        $group_content = $entity;
        $linked_entity = $entity->getEntity();
        $group = $entity->getGroup();
        if ($linked_entity && $linked_entity->getEntityTypeId() === 'node' && $group->id()) {
          $entity_query = $this->entityTypeManager()->getStorage('activity')->getQuery();
          $entity_query->condition('field_activity_entity.target_id', $linked_entity->id(), '=');
          $entity_query->condition('field_activity_entity.target_type', $linked_entity->getEntityTypeId(), '=');
          $entity_query->condition('field_activity_recipient_group', $group->id(), '=');
          $ids = $entity_query->execute();
        }
        break;

      default:
        if ($entity_type !== 'activity') {
          $entity_query = $this->entityTypeManager()->getStorage('activity')->getQuery();
          $entity_query->condition('field_activity_entity.target_id', $entity_id, '=');
          $entity_query->condition('field_activity_entity.target_type', $entity_type, '=');
          $ids = $entity_query->execute();
        }
        break;
    }

    return $ids;
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

    return FALSE;
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
   * ::markEntityNotificationsAsSeen() instead.
   *
   * @todo Change @see to point to a change record.
   * @see https://www.drupal.org/project/social/issues/3087083
   */
  public function markEntityAsRead(AccountInterface $account, EntityBase $entity) {
    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account, [
      ACTIVITY_STATUS_RECEIVED,
      ACTIVITY_STATUS_SEEN,
    ]);

    $this->changeStatusOfActivity($ids, $account);
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
  public function changeStatusOfActivity(array $ids, AccountInterface $account, $status = ACTIVITY_STATUS_RECEIVED): bool {
    if (!empty($ids)) {
      // The transaction opens here.
      $txn = $this->database->startTransaction();
      try {
        // Collect the information about affected rows.
        $this->database->update('activity_notification_status')
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
        $this->getLogger('default')->error($exception->getMessage());
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
          ->condition('uid', (string) $uid);

        if (!empty($status)) {
          $query->condition('status', $status, 'IN');
        }
        return $query->execute()->fetchCol();
      }
      catch (\Exception $exception) {
        // Log the exception to watchdog.
        $this->getLogger('default')->error($exception->getMessage());
        return [];
      }
    }
    return [];
  }

  /**
   * Deletes all entries in activity_notification_table by given ids.
   *
   * @param array $activity_ids
   *   Array of activity ids to be deleted.
   *
   * @return bool
   *   Status of update query.
   */
  public function deleteNotificationsbyIds(array $activity_ids): bool {
    if (!empty($activity_ids)) {
      // The transaction opens here.
      $txn = $this->database->startTransaction();
      try {
        $this->database->delete('activity_notification_status')
          ->condition('aid', $activity_ids, 'IN')
          ->execute();
      }
      catch (\Exception $exception) {
        // Something went wrong somewhere, so roll back now.
        $txn->rollBack();
        // Log the exception to watchdog.
        $this->getLogger('default')->error($exception->getMessage());
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns the activity notification status.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *   Activity entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Activity Notification of current account.
   *
   * @return mixed
   *   FALSE or the status of activity depending upon the execution of query.
   */
  public function getActivityStatus(Activity $activity, AccountInterface $account) {
    // Get the user ID.
    if (!empty($id = $activity->id())) {
      try {
        $query = $this->database->select('activity_notification_status', 'ans')
          ->fields('ans', ['status'])
          ->condition('aid', $id)
          ->condition('uid', $account->id());
        return $query->execute()->fetchField();
      }
      catch (\Exception $exception) {
        // Log the exception to watchdog.
        $this->getLogger('default')->error($exception->getMessage());
      }
    }
    return FALSE;
  }

}
