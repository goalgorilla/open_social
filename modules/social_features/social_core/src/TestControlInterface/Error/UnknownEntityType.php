<?php

declare(strict_types=1);

namespace Drupal\social_core\TestControlInterface\Error;

use Drupal\test_control_interface\Result\OperationErrorBase;

/**
 * Soft failure when an entity type can't be looked up by label.
 */
final readonly class UnknownEntityType extends OperationErrorBase {

  /**
   * Create a new operation error instance.
   *
   * @param string $entityType
   *   The entity type as requested.
   * @param string $reason
   *   Why it can't be looked up, such as "is not a content entity type".
   */
  public function __construct(string $entityType, string $reason) {
    parent::__construct("The entity type '$entityType' $reason, so it can't be looked up by label.");
  }

}
