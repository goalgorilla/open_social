<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no group matches the given title.
 */
final readonly class GroupNotFound extends OperationErrorBase {

  public function __construct(string $title) {
    parent::__construct("Group '$title' was not found.");
  }

}
