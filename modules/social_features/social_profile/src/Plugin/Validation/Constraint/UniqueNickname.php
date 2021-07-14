<?php

namespace Drupal\social_profile\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "UniqueNickname",
 *   label = @Translation("Unique Nickname", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueNickname extends Constraint {

  /**
   * The message that will be shown if the value is not unique.
   *
   * @var string
   */
  public $notUnique = '%value is already taken.';

}
