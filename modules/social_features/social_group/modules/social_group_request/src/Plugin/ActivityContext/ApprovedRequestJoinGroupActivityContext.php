<?php

namespace Drupal\social_group_request\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\social_group_request\Plugin\GroupContentEnabler\GroupMembershipRequest;

/**
 * Provides a 'ApprovedRequestJoinGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "approved_request_join_group_activity_context",
 *  label = @Translation("Approved request to join a group activity context"),
 * )
 */
class ApprovedRequestJoinGroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    if (!empty($data['related_object'])) {
      $referenced_entity = ActivityFactory::getActivityRelatedEntity($data);

      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = $this->entityTypeManager->getStorage('group_content')
        ->load($referenced_entity['target_id']);

      if (
        !$group_content->get('entity_id')->isEmpty() &&
        $group_content->hasField('grequest_status') &&
        !$group_content->get('grequest_status')->isEmpty() &&
        $group_content->get('grequest_status')->value == GroupMembershipRequest::REQUEST_ACCEPTED
      ) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $group_content->get('entity_id')->getString(),
        ];
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity($entity) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    return $entity->getEntityTypeId() === 'group_content';
  }

}
