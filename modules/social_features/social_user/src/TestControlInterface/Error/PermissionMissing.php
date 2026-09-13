<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when the current user lacks an expected permission.
 */
final readonly class PermissionMissing extends OperationErrorBase {

  public function __construct(string $permission) {
    parent::__construct("Current user does not have the permission \"$permission\".");
  }

}
