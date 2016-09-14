<?php

/**
 * @file
 * Contains \Drupal\activity_send\Plugin\ActivityDestination\SendActivityDestinationBase.
 */

namespace Drupal\activity_send\Plugin;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Base class for Activity send destination plugins.
 */
class SendActivityDestinationBase extends ActivityDestinationBase {

  /**
   * Returns message templates for which given destination is enabled.
   */
  public static function getSendMessageTemplates($destination) {
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
   */
  public static function getSendUserSettings($destination, $account) {
    $query = \Drupal::database()->select('user_activity_send', 'uas');
    $query->fields('uas', ['message_template', 'status']);
    $query->condition('uas.uid', $account->id());
    $query->condition('uas.destination', $destination);
    return $query->execute()->fetchAllKeyed();
  }

  /**
   * Set notification settings for given user.
   */
  public static function setSendUserSettings($destination, $account, $values) {
    if (is_object($account) && !empty($values)) {
      foreach ($values as $message_template => $status) {
        $query = \Drupal::database()->merge('user_activity_send');
        $query->fields([
          'uid' => $account->id(),
          'destination' => $destination,
          'message_template' => $message_template,
          'status' => $status
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
   */
  public static function getSendTargetUser($activity) {
    // Get target account.
    if (isset($activity->field_activity_recipient_user) && !empty($activity->field_activity_recipient_user->target_id)) {
      $target_id = $activity->field_activity_recipient_user->target_id;
      $target_account = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->load($target_id);
      return $target_account;
    }
  }

  /**
   * Check if user last activity was more than few minutes ago.
   */
  public static function isUserOffline($account) {
    $query = \Drupal::database()->select('sessions', 's');
    $query->addField('s', 'timestamp');
    $query->condition('s.uid', $account->id());
    $last_activity_time = $query->execute()->fetchField();

    $offline_window = \Drupal::config('download_count.settings')->get('activity_send_offline_window');
    $current_time = REQUEST_TIME - $offline_window;

    return (empty($last_activity_time) || $last_activity_time < $current_time);
  }

}
