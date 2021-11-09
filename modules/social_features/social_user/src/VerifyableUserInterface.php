<?php

declare(strict_types=1);

namespace Drupal\social_user;

use Drupal\user\UserInterface;

/**
 * Provides a user that can be registered but unverified.
 *
 * Verifyng a user means that they have completed site manager defined actions
 * (e.g. filling in their profile) and gain access to platform functionalities.
 */
interface VerifyableUserInterface extends UserInterface {

  /**
   * Check that the user has been verified.
   *
   * @return bool
   *   TRUE if the user is verified, FALSE otherwise.
   */
  public function isVerified() : bool;

}
