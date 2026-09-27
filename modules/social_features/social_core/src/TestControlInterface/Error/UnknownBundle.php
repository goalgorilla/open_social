<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when the entity type has no bundle with the given name.
 */
final readonly class UnknownBundle extends OperationErrorBase {

  public function __construct(string $entityTypeId, string $bundle) {
    parent::__construct("The entity type $entityTypeId has no bundle '$bundle'.");
  }

}
