<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for the operations that add or remove group members.
 *
 * No payload beyond the envelope. Adding a member who already belongs to the
 * group, or removing one who does not, is a no-op, so any count reported
 * here would describe the request rather than what changed.
 */
final readonly class GroupMembersUpdatedSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
