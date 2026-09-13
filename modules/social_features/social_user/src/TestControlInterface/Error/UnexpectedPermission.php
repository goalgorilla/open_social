<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when the current user has a permission it should not have.
 */
final readonly class UnexpectedPermission extends OperationErrorBase {

  public function __construct(string $permission) {
    parent::__construct("Current user has the permission \"$permission\" but should not have it.");
  }

}
