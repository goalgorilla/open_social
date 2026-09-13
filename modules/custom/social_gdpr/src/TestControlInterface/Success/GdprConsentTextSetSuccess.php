<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for gdpr_consent_text_set.
 */
final readonly class GdprConsentTextSetSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
