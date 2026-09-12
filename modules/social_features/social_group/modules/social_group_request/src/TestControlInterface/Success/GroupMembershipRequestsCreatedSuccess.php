<?php

declare(strict_types=1);

namespace Drupal\social_group_request\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for the operations that create group join requests.
 */
final readonly class GroupMembershipRequestsCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $ids
   *   Created group relationship entity IDs, in the order the rows were
   *   given.
   */
  public function __construct(
    public array $ids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'ids' => $this->ids,
    ]);
  }

}
