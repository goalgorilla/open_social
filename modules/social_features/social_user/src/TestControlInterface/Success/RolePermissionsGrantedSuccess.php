<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for role_permission_grant.
 */
final readonly class RolePermissionsGrantedSuccess extends OperationSuccessBase {

  public function __construct(
    public string $roleId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'role_id' => $this->roleId,
    ]);
  }

}
