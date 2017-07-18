<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\GroupMembership;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\activity_creator\ActivityFactory;

/**
 * Provides a 'ContentInMyGroupActivityContext' acitivy context.
 *
 * @ActivityContext(
 *  id = "content_in_my_group_activity_context",
 *  label = @Translation("Content in my group activity context"),
 * )
 */
class ContentInMyGroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {

      $referenced_entity = ActivityFactory::getActivityRelatedEntity($data);

      if ($gid = SocialGroupHelperService::getGroupFromEntity($referenced_entity)) {
        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $gid,
        ];
        $group = Group::load($gid);
        $memberships = GroupMembership::loadByGroup($group);
        foreach ($memberships as $membership) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $membership->getUser()->id(),
          ];
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity($entity) {
    // Check if it's placed in a group (regardless off content type).
    if ($group_entity = GroupContent::loadByEntity($entity)) {
      return TRUE;
    }
    if ($entity->getEntityTypeId() === 'post') {
      if (!empty($entity->get('field_recipient_group')->getValue())) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
