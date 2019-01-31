<?php

namespace Drupal\social_content_report\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is not empty.
 *
 * @Constraint(
 *   id = "ReasonNotEmpty",
 *   label = @Translation("Reason not empty", context = "Validation"),
 *   type = "string"
 * )
 */
class ReasonNotEmptyConstraint extends Constraint {

  public $emptyReason = 'The reason cannot be left empty.';

}
