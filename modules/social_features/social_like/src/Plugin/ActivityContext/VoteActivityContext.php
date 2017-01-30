<?php

/**
 * @file
 * Contains \Drupal\social_like\Plugin\ActivityContext\LikeActivityContext.
 */

namespace Drupal\social_like\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\Entity;
use Drupal\votingapi\Entity\Vote;


/**
 * Provides a 'VoteActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "vote_activity_context",
 *  label = @Translation("Vote activity context"),
 * )
 */
class VoteActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];
      if ($related_object['target_type'] == 'vote') {
        $vote_storage = \Drupal::entityTypeManager()->getStorage('vote');

        $vote = $vote_storage->load($related_object['target_id']);
        if ($vote instanceof Vote) {
          $entity_storage = \Drupal::entityTypeManager()->getStorage($vote->getVotedEntityType());
          /** @var Entity $entity */
          $entity = $entity_storage->load($vote->getVotedEntityId());

          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $entity->getOwnerId(),
          ];
        }
      }
    }
    return $recipients;
  }

  /**
   * @param $entity Entity
   * @return bool
   */
  public function isValidEntity($entity) {
    if ($entity->getEntityTypeId() === 'vote') {
      return TRUE;
    }

    return FALSE;
  }
}
