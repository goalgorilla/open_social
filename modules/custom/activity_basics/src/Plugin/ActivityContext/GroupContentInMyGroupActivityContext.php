<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'GroupContentInMyGroupActivityContext' acitivy context.
 *
 * @ActivityContext(
 *  id = "group_content_in_my_group_activity_context",
 *  label = @Translation("Group content in my group activity context"),
 * )
 */
class GroupContentInMyGroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    if (!empty($data['related_object'])) {
      $referenced_entity = ActivityFactory::getActivityRelatedEntity($data);

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager->getStorage('group_content')
        ->load($referenced_entity['target_id'])
        ->getGroup();

      $memberships = $group->getMembers($group->bundle() . '-group_manager');

      /** @var \Drupal\group\GroupMembership $membership */
      foreach ($memberships as $membership) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $membership->getUser()->id(),
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
