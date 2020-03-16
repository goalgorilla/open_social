<?php

namespace Drupal\social_group_request\Plugin\ActivityAction;

use Drupal\activity_creator\Plugin\ActivityActionBase;

/**
 * Provides 'ChangeStatusGroupMembershipRequestActivityAction' activity action.
 *
 * @ActivityAction(
 *  id = "change_status_group_membership_request_entity_action",
 *  label = @Translation("Action that is triggered when a status of group membership request is changed"),
 * )
 */
class ChangeStatusGroupMembershipRequestActivityAction extends ActivityActionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntity($entity) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    return $entity->getEntityTypeId() === 'group_content';
  }

}
