<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no taxonomy term matches the given name.
 */
final readonly class TermNotFound extends OperationErrorBase {

  public function __construct(string $name) {
    parent::__construct("Term '$name' does not exist.");
  }

}
