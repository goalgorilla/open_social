<?php

namespace Drupal\activity_creator;

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
    $ids = $this->getNotificationIds($account, $status);

    return \Drupal::entityTypeManager()->getStorage('activity')->loadMultiple($ids);
  }

  /**
   * Mark all notifications as Seen for account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   *
   * @return int
   *   Number of remaining notifications.
   */
  public function markAllNotificationsAsSeen(AccountInterface $account): int {
    // Retrieve all the activities referring this entity for this account.
    $ids = $this->getNotificationIds($account);
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function markEntityNotificationsAsRead(AccountInterface $account, EntityBase $entity) {

    // Retrieve all the activities referring this entity for this account.
    $entity_query = $this->entityTypeManager->getStorage('activity')->getQuery();
    $entity_query->condition('field_activity_recipient_user', $account->id(), '=');
    $entity_query->condition('field_activity_destinations', ['notifications'], 'IN');
    $ids = $entity_query->execute();

    // Change the status of this entity.
    $this->changeStatusOfActivity($ids, ACTIVITY_STATUS_READ);

  }

  /**
   * Mark an entity as read for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object.
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity object.
   *
   * @deprecated in opensocial:8.x-7.0 and is removed from opensocial:8.x-7.1. Use
   *   \Drupal\activity_creator\ActivityNotifications
   * ::markEntityNotificationsAsRead() instead.
   *
   * TODO: Change @see to point to a change record.
   * @see https://www.drupal.org/project/social/issues/3087083
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
   *
   * @todo: Remove $entity parameter after deprecations are removed.
   */
  private function getNotificationIds(AccountInterface $account, array $status = [], EntityBase $entity = NULL): array {
    // Get the user ID.
    $uid = $account->id();

    $query = $this->database->select('activity_notification_status', 'ans')
      ->fields('ans', ['aid'])
      ->condition('uid', $uid);

    if (!empty($status)) {
      $query->condition('status', $status, 'IN');
    }
    return $query->execute()->fetchCol();
  }

}
