<?php

namespace Drupal\social_follow_taxonomy\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\activity_creator\ActivityFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides a 'FollowTaxonomyActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *  id = "follow_taxonomy_activity_context",
 *  label = @Translation("Following taxonomy activity context"),
 * )
 */
class FollowTaxonomyActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    // It could happen that a notification has been queued but the account has
    // since been deleted and message author is anonymous.
    if (!empty($data['actor']) && $data['actor'] === 0) {
      return [];
    }

    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node' || $related_entity['target_type'] == 'post') {
        $recipients += $this->getRecipientsWhoFollowTaxonomy($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * List of taxonomy terms.
   */
  public function taxonomyTermsList($entity) {
    $term_ids = social_follow_taxonomy_terms_list($entity);

    return $term_ids;
  }

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

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    switch ($entity->getEntityTypeId()) {
      case 'node':
      case 'post':
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

          // We don't send notifications to content creator.
          if ($recipient->id() !== $entity->getOwnerId() && $entity->access('view', $recipient)) {
            if (!in_array($recipient->id(), array_column($recipients, 'target_id'))) {
              $recipients[] = $recipient->id();
            }
          }
        }
        if (!empty($recipients)) {
          return TRUE;
        }
        break;
    }
    return FALSE;
  }

}
