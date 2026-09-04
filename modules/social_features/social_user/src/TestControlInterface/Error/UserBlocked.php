<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when the named user exists but is blocked.
 */
final readonly class UserBlocked extends OperationErrorBase {

  public function __construct(string $name) {
    parent::__construct("The user '$name' is blocked.");
  }

}
