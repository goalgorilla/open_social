<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for groups_create_with_owner.
 */
final readonly class GroupsWithOwnerCreatedSuccess extends OperationSuccessBase {

  /**
   * Create a new operation result instance.
   *
   * @param list<int> $gids
   *   Created group entity IDs, in the order the rows were given.
   * @param string $owner
   *   The username of the account created to own the groups.
   */
  public function __construct(
    public array $gids,
    public string $owner,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'gids' => $this->gids,
      'owner' => $this->owner,
    ]);
  }

}
