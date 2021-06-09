<?php

namespace Drupal\social_group_invite\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a 'InvitedToGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "invited_to_join_group_activity_context",
 *  label = @Translation("Invited to join a group activity context"),
 * )
 */
class InvitedToGroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    if (!empty($data['related_object'])) {
      // Grab the group_content which holds the user and the group_invitation.
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);
      $storage = $this->entityTypeManager->getStorage('group_content');

      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group_content = $storage->load($referenced_entity['target_id']);

      // Check if the user (entity_id) has a pending invite for the group.
      $properties = [
        'entity_id' => $group_content->getEntity()->id(),
        'gid' => $group_content->getGroup()->id(),
        'invitation_status' => GroupInvitation::INVITATION_PENDING,
      ];
      $invitations = \Drupal::service('ginvite.invitation_loader')->loadByProperties($properties);

      if (!empty($invitations)) {
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
