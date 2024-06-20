<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for fields.
 *
 * @Constraint(
 *   id = "ExternalIdentifierExternalOwnerTargetTypeConstraint",
 *   label = @Translation("Makes sure that target type exist and is allowed", context = "Validation"),
 *   type = {"field"}
 * )
 */
class ExternalIdentifierExternalOwnerTargetTypeConstraint extends Constraint {

  /**
   * The error message when target type is not valid.
   *
   * @var string
   */
  public $invalidTargetTypeMessage = 'Target type "%invalid_target_type" is not valid. Valid target types are: "%allowed_target_types".';

  /**
   * The error message when target type does not exist.
   *
   * @var string
   */
  public $nonexistentTargetTypeMessage = 'The entity type "%entity_type" does not exist.';

}
