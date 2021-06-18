<?php

namespace Drupal\social_follow_tag\Plugin\ActivityContext;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\social_follow_taxonomy\Plugin\ActivityContext\FollowTaxonomyActivityContext;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;

/**
 * Provides a 'FollowTagActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *  id = "follow_tag_activity_context",
 *  label = @Translation("Following tag activity context"),
 * )
 */
class FollowTagActivityContext extends FollowTaxonomyActivityContext {

  /**
   * Returns recipients from followed taxonomies.
   */
  public function getRecipientsWhoFollowTaxonomy(array $related_entity, array $data) {
    $recipients = [];

    $entity = $this->entityTypeManager->getStorage($related_entity['target_type'])
      ->load($related_entity['target_id']);

    if (!empty($entity)) {
      $tids = $this->taxonomyTermsList($entity);
    }

    if (empty($tids)) {
      return [];
    }

    // Get entity group if exists.
    $group = _social_group_get_current_group($entity);

    // Get followers.
    $uids = $this->connection->select('flagging', 'f')
      ->fields('f', ['uid'])
      ->condition('flag_id', 'follow_term')
      ->condition('entity_type', 'taxonomy_term')
      ->condition('entity_id', $tids, 'IN')
      ->groupBy('uid')
      ->execute()->fetchCol();

    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    foreach ($users as $recipient) {
      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      // We don't send notifications to content creator.
      if ($recipient->id() === $entity->getOwnerId()) {
        continue;
      }

      // Do not send notification for inactive user.
      if (
        $recipient->isBlocked() ||
        !$recipient->getLastLoginTime()
      ) {
        continue;
      }

      // Check if user have access to view node.
      if (!$this->haveAccessToNode($recipient, $entity->id())) {
        continue;
      }

      if (is_null($group) || !$group->getMember($recipient)) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $recipient->id(),
        ];
      }
    }

    return $recipients;
  }

}
