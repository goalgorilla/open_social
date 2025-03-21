<?php

namespace Drupal\social_swiftmail\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Utility and helper methods.
 */
class SocialSwiftmailHelper {

  /**
   * Stores settings object.
   */
  protected ConfigFactoryInterface $config;

  /**
   * Constructor an SocialSwiftmailHelper.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * Check if user greeting is disabled for current type of email.
   *
   * @param string $email_type
   *   The email type.
   *
   * @return bool
   *   TRUE if the email type is matched, FALSE otherwise.
   */
  public function disabledGreeting(string $email_type): bool {
    $disabled_greeting_keys = $this->config
      ->get('social_swiftmail.settings')
      ->get('disabled_user_greeting_keys');
    $disabled_greeting_keys = explode(PHP_EOL, $disabled_greeting_keys);

    return in_array($email_type, $disabled_greeting_keys);
  }

  /**
   * Set default disabled user greetings keys.
   */
  public function setDefaultDisabledGreetingKeys(): void {
    $settings = $this->config->getEditable('social_swiftmail.settings');
    $disabled_greeting_keys = $settings->get('disabled_user_greeting_keys');
    $disabled_greeting_keys = array_filter(explode(PHP_EOL, $disabled_greeting_keys));

    $new_disable_greeting_keys = [
      'register_admin_created',
      'register_pending_approval',
      'register_pending_approval_admin',
      'register_no_approval_required',
      'status_activated',
      'status_blocked',
      'cancel_confirm',
      'status_canceled',
      'password_reset',
    ];

    $new_disable_greeting_keys = array_diff($new_disable_greeting_keys, $disabled_greeting_keys);
    foreach ($new_disable_greeting_keys as $new_key) {
      $disabled_greeting_keys[] = $new_key;
    }
    $settings->set('disabled_user_greeting_keys', implode(PHP_EOL, $disabled_greeting_keys))->save();
  }

}
