<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for group_type_tag_add.
 */
final readonly class GroupTypeAddedToGroupSuccess extends OperationSuccessBase {

  public function __construct(
    public int $groupId,
    public int $termId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload([
      'group_id' => $this->groupId,
      'term_id' => $this->termId,
    ]);
  }

}
