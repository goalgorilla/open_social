<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for groups_create_bulk and groups_create_by_current_user.
 */
final readonly class GroupsBulkCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $gids
   *   Created group entity IDs, in the order the rows were given.
   */
  public function __construct(
    public array $gids,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'gids' => $this->gids,
    ]);
  }

}
