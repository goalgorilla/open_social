<?php

namespace Drupal\dynamic_entity_reference\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Dynamic Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Constraint(
 *   id = "ValidDynamicReference",
 *   label = @Translation("Dynamic Entity Reference valid reference", context = "Validation")
 * )
 */
class ValidDynamicReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The referenced entity (%type: %id) does not exist.';

  /**
   * Validation message when the target_id or target_type is empty.
   *
   * @var string
   */
  public $nullMessage = '%property should not be null.';

}
