<?php

/**
 * @file
 * Contains \Drupal\social_group\Plugin\Block\GroupHeroBlock.
 */

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\group\Cache\Context\GroupTypeCacheContext;
use Drupal\group\Cache\Context\GroupMembershipCacheContext;

/**
 * Provides a 'GroupHeroBlock' block.
 *
 * @Block(
 *  id = "group_hero_block",
 *  admin_label = @Translation("Group hero block"),
 *  context = {
 *    "group" = @ContextDefinition("entity:group", required = FALSE)
 *  }
 * )
 */
class GroupHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $group = \Drupal::routeMatch()->getParameter('group');

    if (!is_object($group) && !is_null($group)) {
      $group = \Drupal::service('entity_type.manager')
        ->getStorage('group')
        ->load($group);
    }

    if (!empty($group)) {
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'hero');

      $build['content'] = $content;
    }


    $build['#cache']['contexts'][] = 'group';
    $build['#cache']['contexts'][] = 'group.type';
    $build['#cache']['contexts'][] = 'group_membership.permissions';
    $build['#cache']['contexts'][] = 'group_membership';

    $tags = $build['#cache']['tags'];

    // Based on GroupOperationsBlock.php we add some more cache tags.
    $service_1 = \Drupal::service('group.group_route_context');
    $service_2 = \Drupal::service('current_user');
    $context_1 = new GroupTypeCacheContext($service_1, $service_2);
    $context_2 = new GroupMembershipCacheContext($service_1, $service_2);

    // Merge all cache tags together.
    $merged_tags = Cache::mergeTags(
      $context_1->getCacheableMetadata()->getCacheTags(),
      $context_2->getCacheableMetadata()->getCacheTags()
    );

    // Get all the tags to merge with the new ones.
    foreach($merged_tags as $merged_tag) {
      $tags[] = $merged_tag;
    }

    // This tag will invalidate the hero whenever a new member is added or
    // updated or deleted. group_membership:2
    $tags[] = 'group_membership:' . $group->id();

    $build['#cache']['tags'] = $tags;

    return $build;
  }

}
