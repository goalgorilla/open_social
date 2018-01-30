<?php

namespace Drupal\social_private_message\Service;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message\Service\PrivateMessageService;
use Drupal\user\UserDataInterface;

/**
 * Class SocialPrivateMessageService.
 *
 * @package Drupal\social_private_message\Service
 */
class SocialPrivateMessageService extends PrivateMessageService {

  /**
   * The Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct($mapper, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory, UserDataInterface $userData, Connection $database, Time $time) {
    parent::__construct($mapper, $currentUser, $configFactory, $userData);
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Update the last time the thread was checked by the user.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The thread entity.
   */
  public function updateLastThreadCheckTime(Entity $entity) {
    $this->userData->set('private_message', $this->currentUser->id(), 'private_message_thread:' . $entity->id(), $this->time->getRequestTime());
  }

  /**
   * Remove the thread info from the user_data.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The thread entity.
   */
  public function deleteUserDataThreadInfo(Entity $entity) {
    $this->userData->delete('private_message', $this->currentUser->id(), 'private_message_thread:' . $entity->id());
  }

  /**
   * Update the unread thread count.
   *
   * @return int
   *   The number of unread threads.
   */
  public function updateUnreadCount() {
    // Get the user.
    $uid = $this->currentUser->id();
    // Get all the thread id's for this user.
    $thread_info = $this->getAllThreadIdsForUser($uid);
    // Load these threads.
    $threads = PrivateMessageThread::loadMultiple($thread_info);

    $unread = 0;
    /* @var PrivateMessageThread $thread */
    foreach ($threads as $thread) {
      // Check if the user has a timestamp on the thread and.
      $thread_last_check_user = $this->userData->get('private_message', $uid, 'private_message_thread:' . $thread->id());
      // Check the last time someone other than the current user added
      // something to this thread.
      $thread_last_message = $this->getLastMessageFromOtherUser($uid, $thread->id());
      // Compare  those two.
      if ($thread_last_message > $thread_last_check_user) {
        $unread++;
      }
    }
    return $unread;
  }

  /**
   * Copy function of the one in the private_message module to disable count.
   *
   * {@inheritdoc}
   */
  public function getAllThreadIdsForUser($uid) {
    $query = 'SELECT DISTINCT(thread.id), MAX(thread.updated) ' .
      'FROM {private_message_threads} AS thread ' .
      'JOIN {private_message_thread__members} AS member ' .
      'ON member.entity_id = thread.id AND member.members_target_id = :uid ' .
      'JOIN {private_message_thread__private_messages} AS thread_messages ' .
      'ON thread_messages.entity_id = thread.id ' .
      'JOIN {private_messages} AS messages ' .
      'ON messages.id = thread_messages.private_messages_target_id ' .
      'JOIN {private_message_thread__last_delete_time} AS thread_delete_time ' .
      'ON thread_delete_time.entity_id = thread.id ' .
      'JOIN {pm_thread_delete_time} as owner_delete_time ' .
      'ON owner_delete_time.id = thread_delete_time.last_delete_time_target_id AND owner_delete_time.owner = :uid ' .
      'WHERE owner_delete_time.delete_time <= messages.created ';
    $vars = [':uid' => $uid];

    $query .= 'GROUP BY thread.id ORDER BY MAX(thread.updated) ASC, thread.id';

    $thread_ids = $this->database->query(
      $query,
      $vars
    )->fetchCol();

    return is_array($thread_ids) ? $thread_ids : [];
  }

  /**
   * Last time a message was added to the thread by another user than current.
   *
   * @param int $uid
   *   The user id.
   * @param int $theadid
   *   The thread id.
   *
   * @return int
   *   The timestamp.
   */
  public function getLastMessageFromOtherUser($uid, $theadid) {
    $query = "SELECT MAX(`pm`.`created`) FROM `private_message_thread__private_messages` `pmt`  JOIN `private_messages` `pm` ON `pm`.`id` = `pmt`.`private_messages_target_id` WHERE `pmt`.`entity_id` = :pmt AND `pm`.`owner` <> :uid";
    $vars = [
      ':uid' => $uid,
      ':pmt' => $theadid,
    ];

    $timestamp = $this->database->query(
      $query,
      $vars
    )->fetchCol();

    // Chop of the head.
    if (is_array($timestamp)) {
      $timestamp = $timestamp[0];
    }

    return $timestamp;
  }

}
