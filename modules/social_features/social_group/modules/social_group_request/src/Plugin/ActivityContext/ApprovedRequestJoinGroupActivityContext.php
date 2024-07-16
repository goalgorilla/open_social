<?php

namespace Drupal\social_group_request\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupRelationshipInterface;

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
  public function getRecipients(array $data, int $last_id, int $limit): array {
    $recipients = [];

    if (!empty($data['related_object'])) {
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);

      $storage = $this->entityTypeManager->getStorage('group_content');

      /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_content */
      $group_content = $storage->load($referenced_entity['target_id']);

      if ($group_content instanceof GroupRelationshipInterface) {
        // We use a direct query here rather than using
        // MembershipRequestManager::getMembershipRequest because if there are
        // multiple requests, we want an approved one.
        $properties = [
          'gid' => $group_content->getGroup()->id(),
          'plugin_id' => 'group_membership_request',
          'entity_id' => $group_content->getEntity()->id(),
          'grequest_status' => GroupMembershipRequest::REQUEST_APPROVED,
        ];
        // loadByGroup() doesn't support filters param anymore, lets use
        // loadByProperties() instead.
        $requests = $storage->loadByProperties($properties);

        if (!empty($requests)) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $group_content->getEntity()->id(),
          ];
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    return $entity->getEntityTypeId() === 'group_content';
  }

}
