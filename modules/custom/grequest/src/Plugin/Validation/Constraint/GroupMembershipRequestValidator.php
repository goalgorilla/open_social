<?php

namespace Drupal\grequest\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks user membership.
 */
class GroupMembershipRequestValidator extends ConstraintValidator {

  /**
   * Type-hinting in parent Symfony class is off, let's fix that.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($group_relationship, Constraint $constraint) {

    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    /** @var \Drupal\grequest\Plugin\Validation\Constraint\GroupMembershipRequest $constraint */

    // Apply logic only to group request membership group relationship.
    if ($group_relationship->getPluginId() !== 'group_membership_request') {
      return;
    }

    // Only run our checks if a group was referenced.
    if (!$group = $group_relationship->getGroup()) {
      return;
    }

    // Only run our checks if an entity was referenced.
    if (empty($group_relationship->getEntity())) {
      return;
    }

    if ($group->getMember($group_relationship->getEntity())) {
      $this->context->addViolation($constraint->message, [
        '%name' => $group_relationship->getEntity()->getDisplayName(),
      ]);
    }

  }

}
