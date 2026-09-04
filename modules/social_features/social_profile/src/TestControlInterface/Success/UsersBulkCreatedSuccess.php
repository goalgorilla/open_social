<?php

declare(strict_types=1);

namespace Drupal\social_profile\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for users_create_bulk.
 */
final readonly class UsersBulkCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $uids
   *   Created user IDs.
   */
  public function __construct(
    public array $uids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'uids' => $this->uids,
    ]);
  }

}
