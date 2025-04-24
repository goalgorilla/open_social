<?php

namespace Drupal\social_group_request\Hooks;

use Drupal\hux\Attribute\Alter;

/**
 * Social Group Request alter hooks.
 *
 * @internal
 */
class SocialGroupRequestAlterHooks {

  /**
   * Implements hook_entity_type_alter().
   *
   * Set custom form classes for group request membership forms.
   */
  #[
    Alter('entity_type'),
  ]
  public function groupRequestEntityTypeAlter(array &$entity_types): void {
    if (isset($entity_types['group_content'])) {
      $entity_types['group_content']->setFormClass('group-request-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipRequestForm');
      $entity_types['group_content']->setFormClass('group-approve-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipApproveForm');
      $entity_types['group_content']->setFormClass('group-reject-membership', 'Drupal\social_group_request\Form\GroupRequestMembershipRejectForm');
    }
  }

}
