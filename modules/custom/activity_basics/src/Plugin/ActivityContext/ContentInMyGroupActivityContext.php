<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\group\Entity\Group;
use Drupal\activity_creator\ActivityFactory;
use Drupal\node\Entity\Node;
use Drupal\social_post\Entity\Post;

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
      $owner_id = '';

      if (isset($referenced_entity['target_type']) && $referenced_entity['target_type'] == 'post') {
        $post = Post::load($referenced_entity['target_id']);
        $gid = $post->get('field_recipient_group')->getValue();
        $owner_id = $post->getOwnerId();
      }
      else {
        /* @var \Drupal\group\Entity\GroupContent $group_content_entity */
        $group_content_entity = \Drupal::entityTypeManager()->getStorage('group_content')->load($referenced_entity['target_id']);
        /* @var \Drupal\node\Entity\Node $node */
        $node = $group_content_entity->getEntity();
        if ($node instanceof Node) {
          $owner_id = $node->getOwnerId();
        }
        $gid = $group_content_entity->get('gid')->getValue();
      }

      if ($gid && isset($gid[0]['target_id'])) {
        $target_id = $gid[0]['target_id'];
        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $target_id,
        ];
        $group = Group::load($target_id);
        $memberships = $group->getMembers();

        foreach ($memberships as $membership) {
          // Check if this not the created user.
          if ($owner_id != $membership->getUser()->id()) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $membership->getUser()->id(),
            ];
          }
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
    if ($entity->getEntityTypeId() === 'group_content') {
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
