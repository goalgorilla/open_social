<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result carrying one user's core identity.
 *
 * Shared by the operations that look a single user up: current-user info
 * and lookup by name.
 */
final readonly class UserInfoSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param int $uid
   *   The user ID. 0 for the anonymous user.
   * @param string|null $uuid
   *   The user's UUID, or NULL for the anonymous user.
   * @param string|null $name
   *   The username, or NULL for the anonymous user.
   */
  public function __construct(
    public int $uid,
    public ?string $uuid,
    public ?string $name,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'uid' => $this->uid,
      'uuid' => $this->uuid,
      'name' => $this->name,
    ]);
  }

}
