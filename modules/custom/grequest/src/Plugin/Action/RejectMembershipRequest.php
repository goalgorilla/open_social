<?php

declare(strict_types=1);

namespace Drupal\grequest\Plugin\Action;

use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Reject membership request action.
 *
 * @Action(
 *   id = "grequest_reject",
 *   label = @Translation("Reject membership request"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
final class RejectMembershipRequest extends MembershipRequestActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(GroupRelationshipInterface $entity = NULL) {
    $this->membershipRequestManager->reject($entity);
  }

}
