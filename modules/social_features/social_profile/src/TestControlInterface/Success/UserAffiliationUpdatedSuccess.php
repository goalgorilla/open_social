<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for operations that update a user's affiliation profile.
 */
final readonly class UserAffiliationUpdatedSuccess extends OperationSuccessBase {

  public function __construct(
    public int $profileId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'profile_id' => $this->profileId,
    ]);
  }

}
