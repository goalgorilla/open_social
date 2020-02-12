<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a 'GroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "group_activity_context",
 *   label = @Translation("Group activity context"),
 * )
 */
class GroupActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {

    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {

      $referenced_entity = $data['related_object']['0'];

      try {
        // TODO: Replace this with dependency injection.
        /** @var \Drupal\social_group\SocialGroupHelperService $group_helper */
        $group_helper = \Drupal::service('social_group.helper_service');
      }
      catch (PluginNotFoundException $e) {
        return $recipients;
      }

      if ($gid = $group_helper->getGroupFromEntity($referenced_entity, FALSE)) {
        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $gid,
        ];
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    // Special cases for comments.
    if ($entity->getEntityTypeId() === 'comment') {
      // Returns the entity to which the comment is attached.
      $entity = $entity->getCommentedEntity();
    }

    if (!isset($entity)) {
      return FALSE;
    }

    // Check if it's placed in a group (regardless off content type).
    if (GroupContent::loadByEntity($entity)) {
      return TRUE;
    }

    if ($entity->getEntityTypeId() === 'post') {
      if (!$entity->field_recipient_group->isEmpty()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
