<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ExternalIdentifierEmptySubfieldsConstraint constraint.
 */
class ExternalIdentifierEmptySubfieldsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $item, Constraint $constraint) {
    if (!$constraint instanceof ExternalIdentifierEmptySubfieldsConstraint) {
      return;
    }

    $values = $item->getValue();
    $empty_subfields = [];

    foreach ($values as $key => $value) {
      if ($value === '' or $value === NULL) {
        $empty_subfields[] = $key;
      }
    }

    // It is allowed that all fields are empty.
    if (count($empty_subfields) === count($values)) {
      return;
    }

    if (count($empty_subfields) > 0) {
      // Add label beside subfield machine name.
      $nice_subfield_labels = [];
      foreach ($empty_subfields as $empty_subfield) {
        $nice_subfield_labels[] = $item->getProperties()[$empty_subfield]->getDataDefinition()->getLabel() . ' (' . $empty_subfield . ')';
      }
      $this->context->addViolation($constraint->requiredSubfieldsAreNotSet, [
        '%empty_required_subfield_list' => implode(', ', $nice_subfield_labels),
      ]);
    }

  }

}
