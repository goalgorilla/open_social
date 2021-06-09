<?php

namespace Drupal\social_group_request\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\grequest\Plugin\GroupContentEnabler\GroupMembershipRequest;
use Drupal\Core\Entity\EntityInterface;

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
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);

      $storage = $this->entityTypeManager->getStorage('group_content');

      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = $storage->load($referenced_entity['target_id']);

      $filters = [
        'entity_id' => $group_content->getEntity()->id(),
        'grequest_status' => GroupMembershipRequest::REQUEST_ACCEPTED,
      ];
      $requests = $storage->loadByGroup($group_content->getGroup(), 'group_membership_request', $filters);

      if (!empty($requests)) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $group_content->getEntity()->id(),
        ];
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    return $entity->getEntityTypeId() === 'group_content';
  }

}
