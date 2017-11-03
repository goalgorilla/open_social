<?php

namespace Drupal\social_private_message\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message\Service\PrivateMessageService;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

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
  public function __construct($mapper, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory, UserDataInterface $userData, Connection $database) {
    parent::__construct($mapper, $currentUser, $configFactory, $userData);
    $this->database = $database;
  }

  /**
   * Update the last time the thread was checked by the user.
   *
   * @param $entity
   *    The thread entity.
   */
  public function updateLastThreadCheckTime($entity) {
    $this->userData->set('private_message', $this->currentUser->id(), 'private_message_thread:' . $entity->id(), REQUEST_TIME);
  }

  /**
   * Update the unread thread count.
   *
   * @return int
   *    The number of unread threads.
   */
  public function updateUnreadCount() {
    // Get the user.
    $user = User::load($this->currentUser->id());
    // Get all the thread id's for this user.
    $thread_info = $this->getAllThreadIdsForUser($user);
    // Load these threads.
    $threads = PrivateMessageThread::loadMultiple($thread_info);

    $unread = 0;
    foreach ($threads as $thread) {
      // Check if the user has a timestamp on the thread.
      $user_last_check = $this->userData->get('private_message', $this->currentUser->id(), 'private_message_thread:' . $thread->id());
      // Compare the user_last_check timestamp with the latest updated
      // timestamp from the thread itself.
      /* @var \Drupal\private_message\Entity\PrivateMessageThread $thread */
      if ($thread->getUpdatedTime() > $user_last_check) {
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
  public function getAllThreadIdsForUser(UserInterface $user) {
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
    $vars = [':uid' => $user->id()];

    $query .= 'GROUP BY thread.id ORDER BY MAX(thread.updated) ASC, thread.id';

    $thread_ids = $this->database->query(
      $query,
      $vars
    )->fetchCol();

    return is_array($thread_ids) ? $thread_ids : [];
  }

}
