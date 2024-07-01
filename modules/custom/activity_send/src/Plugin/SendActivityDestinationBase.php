<?php

namespace Drupal\activity_send\Plugin;

use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_creator\Plugin\ActivityDestinationBase;
use Drupal\user\Entity\User;

/**
 * Base class for Activity send destination plugins.
 */
class SendActivityDestinationBase extends ActivityDestinationBase {

  /**
   * Returns message templates for which given destination is enabled.
   *
   * @param string $destination
   *   The destination of notification.
   *
   * @return array
   *   Array consisting of message templates.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getSendMessageTemplates(string $destination) {
    $email_message_templates = [];
    /** @var \Drupal\message\MessageTemplateInterface[] $message_templates */
    $message_templates = \Drupal::entityTypeManager()
      ->getStorage('message_template')
      ->loadMultiple();
    foreach ($message_templates as $message_template) {
      $destinations = $message_template->getThirdPartySetting('activity_logger', 'activity_destinations', NULL);
      if (is_array($destinations) && in_array($destination, $destinations)) {
        $email_message_templates[$message_template->id()] = $message_template->getDescription();
      }
    }
    return $email_message_templates;
  }

  /**
   * Returns notification settings of given user.
   *
   * @param string $destination
   *   The destination of notification.
   * @param \Drupal\social_user\Entity\User $account
   *   The user account object.
   *
   * @return mixed
   *   The array of user settings.
   */
  public static function getSendUserSettings(string $destination, User $account) {
    $query = \Drupal::database()->select('user_activity_send', 'uas');
    $query->fields('uas', ['message_template', 'frequency']);
    $query->condition('uas.uid', $account->id());
    $query->condition('uas.destination', $destination);
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Get notification settings of all given user IDs.
   *
   * @param string $destination
   *   The destination of notification.
   * @param array $account_ids
   *   The array of account ids for which the settings are needed.
   * @param string $message_template_id
   *   The machine name of message template.
   *
   * @return mixed
   *   Array of the uids and frequencies, keyed by uid.
   */
  public static function getSendAllUsersSetting(string $destination, array $account_ids, string $message_template_id) {
    $query = \Drupal::database()->select('user_activity_send', 'uas');
    $query->fields('uas', ['uid', 'frequency']);
    $query->condition('uas.uid', $account_ids, 'IN');
    $query->condition('uas.destination', $destination);
    $query->condition('uas.message_template', $message_template_id);
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Get user IDs for given frequency.
   *
   * @param string $destination
   *   The destination of notification.
   * @param array $account_ids
   *   The array of array ids.
   * @param string $frequency
   *   The frequency for which we need to check.
   * @param string $message_template_id
   *   The machine name of message template.
   *
   * @return mixed
   *   The array of user ids.
   */
  public static function getSendUserIdsByFrequency(string $destination, array $account_ids, string $frequency, string $message_template_id) {
    $query = \Drupal::database()->select('user_activity_send', 'uas');
    $query->fields('uas', ['uid']);
    $query->condition('uas.uid', $account_ids, 'IN');
    $query->condition('uas.frequency', $frequency);
    $query->condition('uas.destination', $destination);
    $query->condition('uas.message_template', $message_template_id);
    $query->distinct();
    return $query->execute()->fetchAllKeyed(0, 0);
  }

  /**
   * Set notification settings for given user.
   *
   * @param string $destination
   *   The destination of notification.
   * @param \Drupal\social_user\Entity\User $account
   *   The user entity object.
   * @param array $values
   *   The values to set.
   *
   * @throws \Exception
   */
  public static function setSendUserSettings(string $destination, User $account, array $values) {
    if (is_object($account) && !empty($values)) {
      foreach ($values as $message_template => $frequency) {
        $query = \Drupal::database()->merge('user_activity_send');
        $query->fields([
          'uid' => $account->id(),
          'destination' => $destination,
          'message_template' => $message_template,
          'frequency' => $frequency,
        ]);
        $query->keys([
          'uid' => $account->id(),
          'destination' => $destination,
          'message_template' => $message_template,
        ]);
        $query->execute();
      }
    }
  }

  /**
   * Returns target account.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *   The activity entity.
   *
   * @return \Drupal\user\Entity\User|null
   *   The target user account object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getSendTargetUser(Activity $activity) {
    // Get target account.
    if (isset($activity->field_activity_recipient_user) && !empty($activity->field_activity_recipient_user->target_id)) {
      $target_id = $activity->field_activity_recipient_user->target_id;
      /** @var \Drupal\user\Entity\User $target_account */
      $target_account = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->load($target_id);
      return $target_account;
    }
    return NULL;
  }

  /**
   * Get one or multiple target user accounts.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *   The activity from which the users need to be targeted.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Returns an array of target user accounts.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getSendTargetUsers(Activity $activity) {
    $targets = [];
    if (isset($activity->field_activity_recipient_user) && !empty($activity->field_activity_recipient_user)) {
      $targets = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadMultiple(array_column($activity->field_activity_recipient_user->getValue(), 'target_id'));
    }
    return $targets;
  }

  /**
   * Check if user last activity was more than few minutes ago.
   *
   * @param \Drupal\user\Entity\User $account
   *   The account to check.
   *
   * @return bool
   *   Status of user.
   */
  public static function isUserOffline(User $account) {
    $query = \Drupal::database()->select('sessions', 's');
    $query->addField('s', 'timestamp');
    $query->condition('s.uid', $account->id());
    $last_activity_time = $query->execute()->fetchField();

    $offline_window = \Drupal::config('activity_send.settings')->get('activity_send_offline_window');
    $current_time = \Drupal::time()->getRequestTime() - $offline_window;

    return (empty($last_activity_time) || $last_activity_time < $current_time);
  }

}
