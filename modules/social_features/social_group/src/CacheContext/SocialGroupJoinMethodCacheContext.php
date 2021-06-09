<?php

namespace Drupal\social_group\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\group\Entity\Group;

/**
 * Class SocialGroupJoinMethodCacheContext.
 */
class SocialGroupJoinMethodCacheContext implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    \Drupal::messenger()->addMessage('Cache context for the join methods');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // This will return the join method of the current group.
    $group = _social_group_get_current_group();
    if ($group instanceof Group && $group->hasField('field_group_allowed_join_method')) {
      if (!empty($group->getFieldValue('field_group_allowed_join_method', 'value'))) {
        return $group->getFieldValue('field_group_allowed_join_method', 'value');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
