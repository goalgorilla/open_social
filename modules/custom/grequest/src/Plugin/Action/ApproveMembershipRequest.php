<?php

declare(strict_types=1);

namespace Drupal\grequest\Plugin\Action;

use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Approve membership request action.
 *
 * @Action(
 *   id = "grequest_approve",
 *   label = @Translation("Approve membership request"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
final class ApproveMembershipRequest extends MembershipRequestActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(GroupRelationshipInterface $entity = NULL) {
    $this->membershipRequestManager->approve($entity);
  }

}
