<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for logging a session in. No payload beyond the envelope.
 *
 * Who the session now belongs to travels on the framework's own
 * `OS-TCI-Session-Name` response header, not in this body — see the
 * test_control_interface README's `changesSession` documentation.
 */
final readonly class LoginSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
