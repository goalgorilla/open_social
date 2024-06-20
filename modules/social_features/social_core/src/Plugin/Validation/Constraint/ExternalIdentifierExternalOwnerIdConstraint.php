<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for external identifier field.
 *
 * Prevents field values from being saved if the external owner entity does not
 * exist.
 *
 * @Constraint(
 *   id = "ExternalIdentifierExternalOwnerIdConstraint",
 *   label = @Translation("Makes sure that External Owner Entity exist", context = "Validation"),
 *   type = {"field"}
 * )
 */
class ExternalIdentifierExternalOwnerIdConstraint extends Constraint {
  /**
   * The error message when entity does not exist.
   *
   * @var string
   */
  public $nonexistentIdMessage = 'The entity of type "%entity_type" and ID "%entity_id" does not exist.';

}
