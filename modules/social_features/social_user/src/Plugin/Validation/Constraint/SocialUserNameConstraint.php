<?php

namespace Drupal\social_user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid user name.
 *
 * @Constraint(
 *   id = "SocialUserName",
 *   label = @Translation("Social User name", context = "Validation"),
 * )
 */
class SocialUserNameConstraint extends Constraint {

  /**
   * The error message for this constraint.
   */
  public string $usernameIsEmailMessage = 'The username can not be an email address.';

}
