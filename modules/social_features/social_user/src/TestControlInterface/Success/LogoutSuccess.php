<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for logout_user. No payload beyond the standard envelope.
 */
final readonly class LogoutSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
