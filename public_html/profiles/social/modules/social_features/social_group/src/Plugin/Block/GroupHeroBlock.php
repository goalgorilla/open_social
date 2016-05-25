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

    // Also see GroupOperationsBlock.php for cache settings.
    // This sets context per group type (private in future), per permission
    // since some users may have the same roles in different group types granting
    // them other permissions.
    $build['#cache']['contexts'] = ['group.type', 'group_membership.permissions'];
    $build['#cache']['contexts'][] = 'group_membership';

    $service_1 = \Drupal::service('group.group_route_context');
    $service_2 = \Drupal::service('current_user');

    // Add's route based and current user based cache tags for grouptype & membership.
    $context_1 = new GroupTypeCacheContext($service_1, $service_2);
    $context_2 = new GroupMembershipCacheContext($service_1, $service_2);

    // Merge them with existing tags!
    $tags = Cache::mergeTags(
      $context_1->getCacheableMetadata()->getCacheTags(),
      $context_2->getCacheableMetadata()->getCacheTags()
    );

    // Also add custom cache_tag for when a member is added to the group so we
    // can invalidate. It will be group_membership:gid.
    $tags[] = 'group_membership:' . $group->id();

    $build['#cache']['tags'] = $tags;

    $build['#cache'] = array(
      'max-age' => 0;
    );

    return $build;
  }

}
