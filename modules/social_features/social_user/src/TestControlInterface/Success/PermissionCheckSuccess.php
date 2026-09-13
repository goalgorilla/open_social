<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for the current user's permission assertions.
 *
 * Shared by both assertions: the permission checked and who it was checked
 * for, so a passing step still says what it looked at.
 */
final readonly class PermissionCheckSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param string $permission
   *   The permission that was checked.
   * @param int $uid
   *   The user ID it was checked for. 0 for the anonymous user.
   */
  public function __construct(
    public string $permission,
    public int $uid,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'permission' => $this->permission,
      'uid' => $this->uid,
    ]);
  }

}
