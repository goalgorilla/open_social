<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no entity of the bundle has the given label.
 */
final readonly class EntityNotFound extends OperationErrorBase {

  public function __construct(string $entityTypeId, string $bundle, string $label) {
    parent::__construct("No $entityTypeId of bundle '$bundle' with label '$label' was found.");
  }

}
