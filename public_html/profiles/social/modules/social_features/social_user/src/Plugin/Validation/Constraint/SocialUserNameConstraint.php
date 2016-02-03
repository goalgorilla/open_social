<?php

/**
 * @file
 * Contains \Drupal\social_user\Plugin\Validation\Constraint\UserNameConstraint.
 */

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

  public $usernameIsEmailMessage = 'The username can not be an email address.';

}
