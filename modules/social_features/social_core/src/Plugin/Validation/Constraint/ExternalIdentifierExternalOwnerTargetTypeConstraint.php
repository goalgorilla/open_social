<?php

namespace Drupal\social_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a custom constraint for external identifier field.
 *
 * Prevents field values from being stored if the referenced target type is not
 * on the list of allowed ones. The list of allowed ones is defined by the field
 * storage configuration and may contain more than one allowed target type.
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

  /**
   * The error message, when there are no target type is supported.
   *
   * @var string
   */
  public $noAvailableTargetTypes = 'Currently, there are no available target types (allowed entity types). Please contact the system administrator to enable at least one target type.';

}
