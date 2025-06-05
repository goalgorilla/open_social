<?php

namespace Drupal\social_user\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\hux\Attribute\Hook;
use Drupal\user\UserInterface;

/**
 * Defines a class for handling user role identification hooks.
 *
 * @internal
 */
class RoleIdentificationHooks {

  /**
   * Constructs a RoleIdentificationHooks object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempstore
   *   The private tempstore factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   */
  public function __construct(
    private PrivateTempStoreFactory $tempstore,
    private ConfigFactoryInterface $config,
  ) {
  }

  /**
   * Sets the login flag in tempstore when a user logs in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   */
  #[Hook('user_login')]
  public function onUserLogin(UserInterface $account): void {
    if ($account->isAnonymous()) {
      return;
    }

    // Don't bother storing anything if no roles are configured.
    $tracked_roles = $this->config->get('social_user.settings')->get('tracked_roles') ?? [];
    if (empty($tracked_roles)) {
      return;
    }

    $tempstore = $this->tempstore->get('social_user');
    $tempstore->set('role_identification_login', TRUE);
  }

}
