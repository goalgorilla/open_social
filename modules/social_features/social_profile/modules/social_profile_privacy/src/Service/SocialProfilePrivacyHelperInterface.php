<?php

namespace Drupal\social_profile_privacy\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the helper service interface.
 *
 * @package Drupal\social_profile_privacy\Service
 */
interface SocialProfilePrivacyHelperInterface {

  /**
   * Always show profile field for everyone.
   */
  const SHOW = 0;

  /**
   * Show profile field, but it can be hidden by a user.
   */
  const CONFIGURABLE = 1;

  /**
   * Hide profile field for everyone.
   */
  const HIDE = 2;

  /**
   * Returns field settings of a user profile.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return array
   *   The array of field labels and accesses, keyed by field name.
   */
  public function getFieldOptions(AccountInterface $account = NULL);

}
