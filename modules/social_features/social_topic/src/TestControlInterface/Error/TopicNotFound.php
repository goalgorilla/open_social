<?php

declare(strict_types=1);

namespace Drupal\social_topic\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when no unique topic matches the given title.
 */
final readonly class TopicNotFound extends OperationErrorBase {

  public function __construct(string $title) {
    parent::__construct("The topic '$title' was not found.");
  }

}
