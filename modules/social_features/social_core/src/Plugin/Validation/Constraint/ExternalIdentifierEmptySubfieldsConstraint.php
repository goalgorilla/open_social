<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for fields.
 *
 * @Constraint(
 *   id = "ExternalIdentifierEmptySubfieldsConstraint",
 *   label = @Translation("Makes sure that all required subfields values are set", context = "Validation"),
 *   type = {"field"}
 * )
 */
class ExternalIdentifierEmptySubfieldsConstraint extends Constraint {

  /**
   * The error message when all required subfields values not are set.
   *
   * @var string
   */
  public $requiredSubfieldsAreNotSet = 'Not all required subfields have been set. Please insert values for: %empty_required_subfield_list.';

}
