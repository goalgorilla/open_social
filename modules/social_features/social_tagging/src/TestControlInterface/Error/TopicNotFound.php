<?php

declare(strict_types=1);

namespace Drupal\social_tagging\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no topic matches the given title.
 */
final readonly class TopicNotFound extends OperationErrorBase {

  public function __construct(string $title) {
    parent::__construct("Topic '$title' was not found.");
  }

}
