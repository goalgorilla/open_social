<?php

namespace Drupal\social_private_message\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Mapper\PrivateMessageMapperInterface;
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
   * {@inheritdoc}
   */
  public function __construct(PrivateMessageMapperInterface $mapper, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory, UserDataInterface $userData, CacheTagsInvalidatorInterface $cacheTagsInvalidator, EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, Connection $database) {
    parent::__construct($mapper, $currentUser, $configFactory, $userData, $cacheTagsInvalidator, $entityTypeManager, $time);

    $this->database = $database;
  }

  /**
   * Update the last time the thread was checked by the user.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The thread entity.
   */
  public function updateLastThreadCheckTime(EntityBase $entity) {
    $this->userData->set('private_message', $this->currentUser->id(), 'private_message_thread:' . $entity->id(), $this->time->getRequestTime());
  }

  /**
   * Remove the thread info from the user_data.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The thread entity.
   */
  public function deleteUserDataThreadInfo(EntityBase $entity) {
    $this->userData->delete('private_message', $this->currentUser->id(), 'private_message_thread:' . $entity->id());
  }

  /**
   * Update the unread thread count.
   *
   * @return int
   *   The number of unread threads.
   */
  public function updateUnreadCount() {
    $unread = 0;

    // Get the user.
    $uid = $this->currentUser->id();
    // Get all the thread id's for this user.
    $threads = $this->getAllThreadIdsForUser($uid);

    if (empty($threads)) {
      return $unread;
    }

    // Check the last time someone other than the current user added
    // something to the threads.
    $thread_last_messages = $this->getLastMessagesFromOtherUsers($uid, $threads);
    if (empty($thread_last_messages)) {
      return $unread;
    }

    foreach ($thread_last_messages as $thread_id => $last_message) {
      // Check if the user has a timestamp on the thread.
      $thread_last_check = $this->userData->get('private_message', $uid, 'private_message_thread:' . $thread_id);
      if ($thread_last_check === NULL) {
        $thread_last_check = 0;
      }

      // Check if someone send a message after your last check.
      if ($last_message > $thread_last_check) {
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

    $query .= 'GROUP BY thread.id ORDER BY MAX(thread.updated) DESC, thread.id';

    $thread_ids = $this->database->query(
      $query,
      $vars
    )->fetchCol();

    return is_array($thread_ids) ? $thread_ids : [];
  }

  /**
   * Retrieves times of last message in all threads send by other users.
   *
   * @param int $uid
   *   The user uid to check for.
   * @param array $threads
   *   List of thread IDs to che check for.
   *
   * @return array
   *   A list of timestamps linked to the thread IDs.
   */
  public function getLastMessagesFromOtherUsers($uid, array $threads) {
    return $this->database->query(
      'SELECT MAX(pm.created), pmt.entity_id ' .
      'FROM {private_message_thread__private_messages} pmt ' .
      'LEFT JOIN {private_messages} pm ON pmt.private_messages_target_id = pm.id ' .
      'WHERE pmt.entity_id IN (:threads[]) AND pm.owner <> :uid ' .
      'GROUP BY pmt.entity_id',
      [
        ':threads[]' => $threads,
        ':uid' => $uid,
      ]
    )->fetchAllKeyed(1, 0);
  }

  /**
   * Last time a message was added to the thread by another user than current.
   *
   * @param int $uid
   *   The user id.
   * @param int $thread_id
   *   The thread id.
   *
   * @return int
   *   The timestamp or 0 if nothing was found.
   */
  public function getLastMessageFromOtherUser($uid, $thread_id) {
    $timestamp = $this->database->query(
      'SELECT MAX(pm.created) ' .
      'FROM {private_message_thread__private_messages} pmt ' .
      'JOIN {private_messages} pm ON pmt.private_messages_target_id = pm.id ' .
      'WHERE pmt.entity_id = :thread AND pm.owner <> :uid',
      [
        ':thread' => $thread_id,
        ':uid' => $uid,
      ]
    )->fetchCol();

    // Chop of the head.
    if (is_array($timestamp)) {
      $timestamp = ($timestamp[0] !== NULL) ? $timestamp[0] : 0;
    }

    return $timestamp;
  }

}
