<?php

namespace Drupal\social_mentions\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides a 'MentionsComment' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "mention_comment",
 *  label = @Translation("Mentions in a comment"),
 *  entities = {"mentions" = {}}
 * )
 */
class MentionCommentActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition(ContentEntityInterface $entity): bool {
    if ($entity->getEntityTypeId() === 'mentions') {
      if (isset($entity->entity_type) && $entity->entity_type->value === 'comment') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
