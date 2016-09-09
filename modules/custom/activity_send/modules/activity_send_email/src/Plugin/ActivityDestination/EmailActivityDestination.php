<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination.
 */

namespace Drupal\activity_send_email\Plugin\ActivityDestination;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;

/**
 * Provides a 'EmailActivityDestination' activity destination.
 *
 * @ActivityDestination(
 *  id = "email",
 *  label = @Translation("Email"),
 * )
 */
class EmailActivityDestination extends SendActivityDestinationBase {

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailMessageTemplates() {
    return parent::getSendMessageTemplates('email');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailUserSettings($account) {
    return parent::getSendUserSettings('email', $account);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSendEmailUserSettings($account, $values) {
    parent::setSendUserSettings('email', $account, $values);
  }

}
