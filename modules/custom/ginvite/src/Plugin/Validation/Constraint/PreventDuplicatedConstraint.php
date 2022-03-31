<?php

namespace Drupal\ginvite\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Prevent duplicated invitations from being created.
 *
 * @Constraint(
 *   id = "PreventDuplicated",
 *   label = @Translation("Prevent duplicated invitations from being created", context = "Validation"),
 *   type = "entity:group_content"
 * )
 */
class PreventDuplicatedConstraint extends CompositeConstraintBase {

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['invitee_mail', 'gid', 'entity_id'];
  }

}
