<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no term matches the given name in the tagging vocabulary.
 */
final readonly class TagNotFound extends OperationErrorBase {

  public function __construct(string $name) {
    parent::__construct("Tag '$name' was not found in the social_tagging vocabulary.");
  }

}
