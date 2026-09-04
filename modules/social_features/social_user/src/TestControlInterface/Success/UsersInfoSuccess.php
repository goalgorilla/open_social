<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result carrying multiple users' core identities.
 */
final readonly class UsersInfoSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<array{uid: int, uuid: string|null, name: string|null}> $users
   *   One entry per requested name, in the order requested.
   */
  public function __construct(
    public array $users,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'users' => $this->users,
    ]);
  }

}
