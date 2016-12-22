<?php

/**
 * @file
 * Contains \Drupal\social_mention\Plugin\ActivityEntityCondition\MentionPostActivityEntityCondition.
 */

namespace Drupal\social_mentions\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;

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
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() === 'mentions') {
      if (isset($entity->entity_type) && $entity->entity_type->value == 'post') {
        return TRUE;
      }
    }
    return FALSE;
  }

}
