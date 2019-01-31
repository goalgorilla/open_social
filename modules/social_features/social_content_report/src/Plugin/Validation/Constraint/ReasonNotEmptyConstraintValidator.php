<?php

namespace Drupal\social_content_report\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class ReasonNotEmptyConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    // If we have no items this means the text was left blank.
    if ($items->isEmpty()) {
      $this->context->addViolation($constraint->emptyReason);
    }
  }

}
