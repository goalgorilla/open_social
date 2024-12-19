<?php

namespace Drupal\social_mentions\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a 'MentionPost' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "mention_post",
 *  label = @Translation("Mention in a post"),
 *  entities = {"mentions" = {}}
 * )
 */
class MentionPostActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition(ContentEntityInterface $entity): bool {
    if ($entity->getEntityTypeId() === 'mentions') {
      if (isset($entity->entity_type) && $entity->entity_type->value === 'post') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
