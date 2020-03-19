<?php

namespace Drupal\social_follow_tag\Plugin\ActivityContext;

use Drupal\social_follow_taxonomy\Plugin\ActivityContext\FollowTaxonomyActivityContext;

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
   * {@inheritdoc}
   */
  public function taxonomyTermsList($entity) {
    $taxonomy_ids = [];
    if ($entity->hasField('social_tagging')) {
      if (!empty($entity->get('social_tagging')->getValue())) {
        $tags = $entity->get('social_tagging')->getValue();

        foreach ($tags as $tag) {
          $taxonomy_ids[] = $tag['target_id'];
        }
      }
    }

    return $taxonomy_ids;
  }

}
