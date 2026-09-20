<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for affiliation config-change operations.
 */
final readonly class AffiliationConfiguredSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
