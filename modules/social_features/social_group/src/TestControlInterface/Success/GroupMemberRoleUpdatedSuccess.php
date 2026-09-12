<?php

declare(strict_types=1);

namespace Drupal\social_group\TestControlInterface\Success;

use Drupal\test_control_interface\Result\OperationSuccessBase;

/**
 * Success result for the operations that change a group member's roles.
 *
 * No payload beyond the envelope; the member, group and role are all named
 * by the request.
 */
final readonly class GroupMemberRoleUpdatedSuccess extends OperationSuccessBase {

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return $this->okPayload();
  }

}
