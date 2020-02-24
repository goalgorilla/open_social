<?php

namespace Drupal\social_follow_taxonomy\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\activity_creator\ActivityFactory;
use Drupal\user\UserInterface;

/**
 * Provides a 'FollowTaxonomyActivityContext' activity context plugin.
 */
class FollowTaxonomyActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = ActivityFactory::getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node') {
        $recipients += $this->getRecipientsWhoFollowContent($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * List of taxonomy terms.
   */
  public function taxonomyTermsList($entity) {
    return [];
  }

  /**
   * Returns owner recipient from entity.
   */
  public function getRecipientsWhoFollowContent(array $related_entity, array $data) {
    $recipients = [];
    $storage = \Drupal::entityTypeManager()->getStorage('flagging');

    $entity = $this->entityTypeManager->getStorage($related_entity['target_type'])
      ->load($related_entity['target_id']);
    $tids = $this->taxonomyTermsList($entity);

    if (empty($tids)) {
      return [];
    }

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
        break;
      }

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

    return $recipients;
  }

}
