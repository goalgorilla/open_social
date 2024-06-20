<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for external identifier field.
 *
 * Prevents field values from being saved if not all required subfield values
 * are set. This can be either that all subfield values are empty, or that all
 * subfield values are provided but partially filled subfield values are not
 * allowed.
 *
 * @Constraint(
 *   id = "ExternalIdentifierEmptySubfieldsConstraint",
 *   label = @Translation("Makes sure that all required subfields values are set", context = "Validation"),
 *   type = {"field"}
 * )
 */
class ExternalIdentifierEmptySubfieldsConstraint extends Constraint {

  /**
   * The error message when all required subfield values are not set.
   *
   * @var string
   */
  public $requiredSubfieldsAreNotSet = 'Not all required subfields have been set. Please insert values for: %empty_required_subfield_list.';

}
