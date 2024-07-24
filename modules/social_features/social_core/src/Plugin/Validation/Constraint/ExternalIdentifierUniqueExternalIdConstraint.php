<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for external identifier field.
 *
 * Prevents field values from being stored if external id value is not unique
 * per external owner.
 *
 * @Constraint(
 *   id = "ExternalIdentifierUniqueExternalIdConstraint",
 *   label = @Translation("Makes sure that external id is unique per external owner", context = "Validation"),
 *   type = {"field"}
 * )
 */
class ExternalIdentifierUniqueExternalIdConstraint extends Constraint {

  /**
   * The error message when external id is not unique per external owner.
   *
   * @var string
   */
  public $externalIdNotUniqueMessage = 'The external identifier ID must be unique. The External ID "%external_id" is already in use.';

}
