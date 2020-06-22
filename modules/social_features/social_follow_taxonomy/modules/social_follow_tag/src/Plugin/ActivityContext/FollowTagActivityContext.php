<?php

namespace Drupal\social_follow_tag\Plugin\ActivityContext;

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

    $storage = $this->entityTypeManager->getStorage('flagging');
    $flaggings = $storage->loadByProperties([
      'flag_id' => 'follow_term',
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tids,
    ]);

    foreach ($flaggings as $flagging) {
      /* @var $flagging \Drupal\flag\FlaggingInterface */
      $recipient = $flagging->getOwner();

      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      $group = _social_group_get_current_group($entity);
      if ($group instanceof GroupInterface) {
        if (!$group->getMember($recipient)) {
          // We don't send notifications to content creator.
          if ($recipient->id() !== $entity->getOwnerId() && $entity->access('view', $recipient)) {
            if (!in_array($recipient->id(), array_column($recipients, 'target_id'))) {
              $recipients[] = [
                'target_type' => 'user',
                'target_id' => $recipient->id(),
              ];
            }
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
      case 'node':
        $recipients = [];
        $tids = $this->taxonomyTermsList($entity);

        if (empty($tids)) {
          return FALSE;
        }

        $storage = $this->entityTypeManager->getStorage('flagging');
        $flaggings = $storage->loadByProperties([
          'flag_id' => 'follow_term',
          'entity_type' => 'taxonomy_term',
          'entity_id' => $tids,
        ]);

        foreach ($flaggings as $flagging) {
          /* @var $flagging \Drupal\flag\FlaggingInterface */
          $recipient = $flagging->getOwner();

          if (!$recipient instanceof UserInterface) {
            continue;
          }

          $group = _social_group_get_current_group($entity);
          if ($group instanceof GroupInterface) {
            if (!$group->getMember($recipient)) {
              // We don't send notifications to content creator.
              if ($recipient->id() !== $entity->getOwnerId() && $entity->access('view', $recipient)) {
                if (!in_array($recipient->id(), array_column($recipients, 'target_id'))) {
                  $recipients[] = $recipient->id();
                }
              }
            }
          }
        }
        if (!empty($recipients)) {
          return TRUE;
        }
    }
    return FALSE;
  }

}
