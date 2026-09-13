<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no term matches the name in the group_type vocabulary.
 */
final readonly class GroupTypeNotFound extends OperationErrorBase {

  public function __construct(string $name) {
    parent::__construct("Group type '$name' was not found in the group_type vocabulary.");
  }

}
