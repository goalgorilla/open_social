<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'ContentInMyGroupActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "content_in_my_group_activity_context",
 *   label = @Translation("Content in my group activity context"),
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
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);
      $owner_id = '';

      if (isset($referenced_entity['target_type']) && $referenced_entity['target_type'] === 'post') {
        try {
          /** @var \Drupal\social_post\Entity\PostInterface $post */
          $post = $this->entityTypeManager->getStorage('post')
            ->load($referenced_entity['target_id']);
        }
        catch (PluginNotFoundException $exception) {
          return $recipients;
        }

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if (!$post) {
          return $recipients;
        }

        $gid = $post->get('field_recipient_group')->getValue();
        $owner_id = $post->getOwnerId();
      }
      else {
        /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
        $group_content = $this->entityTypeManager->getStorage('group_content')
          ->load($referenced_entity['target_id']);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if (!$group_content) {
          return $recipients;
        }

        $node = $group_content->getEntity();

        if ($node instanceof NodeInterface) {
          $owner_id = $node->getOwnerId();

          if (!$node->isPublished()) {
            return $recipients;
          }
        }

        $gid = $group_content->get('gid')->getValue();
      }

      if ($gid && isset($gid[0]['target_id'])) {
        $target_id = $gid[0]['target_id'];

        $recipients[] = [
          'target_type' => 'group',
          'target_id' => $target_id,
        ];

        $group = $this->entityTypeManager->getStorage('group')
          ->load($target_id);

        // It could happen that a notification has been queued but the content
        // has since been deleted. In that case we can find no additional
        // recipients.
        if (!$group instanceof GroupInterface) {
          return $recipients;
        }

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
  public function isValidEntity(EntityInterface $entity) {
    switch ($entity->getEntityTypeId()) {
      case 'group_content':
        return TRUE;

      case 'post':
        return !$entity->field_recipient_group->isEmpty();

      default:
        return FALSE;
    }
  }

}
