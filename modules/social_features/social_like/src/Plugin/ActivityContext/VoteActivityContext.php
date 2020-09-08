<?php

namespace Drupal\social_like\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\votingapi\VoteInterface;

/**
 * Provides a 'VoteActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "vote_activity_context",
 *   label = @Translation("Vote activity context"),
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

      if ($related_object['target_type'] === 'vote') {
        $vote_storage = $this->entityTypeManager->getStorage('vote');
        $vote = $vote_storage->load($related_object['target_id']);

        if ($vote instanceof VoteInterface) {
          $entity_storage = $this->entityTypeManager->getStorage($vote->getVotedEntityType());

          /** @var \Drupal\Core\Entity\EntityInterface $entity */
          $entity = $entity_storage->load($vote->getVotedEntityId());

          $uid = $entity->getOwnerId();

          // Don't send notifications to myself.
          if ($uid !== $data['actor']) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $uid,
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
    return $entity->getEntityTypeId() === 'vote';
  }

}
