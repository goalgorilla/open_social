<?php

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

    // Add cache tags for group type & permissions. We need join / leave for
    // the CTA and also update permissions for the button to edit a group.
    $build['#cache']['contexts'][] = 'group.type';
    $build['#cache']['contexts'][] = 'group_membership';

    if (!empty($group)) {
      // Content
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'hero');

      $build['content'] = $content;

//      // Cache
//      $renderer = \Drupal::service('renderer');
//      $current_user = \Drupal::currentUser();
//      $membership = $group->getMember($current_user);
//
//      $tags = empty($build['#cache']['tags']) ? array() : $build['#cache']['tags'];
//
//      // Based on GroupOperationsBlock.php we add some more cache tags.
//      $service_1 = \Drupal::service('group.group_route_context');
//      $service_2 = \Drupal::service('current_user');
//      $context_1 = new GroupTypeCacheContext($service_1, $service_2);
//      $context_2 = new GroupMembershipCacheContext($service_1, $service_2);
//      // Merge all cache tags together.
//      $merged_tags = Cache::mergeTags(
//        $context_1->getCacheableMetadata()->getCacheTags(),
//        $context_2->getCacheableMetadata()->getCacheTags()
//      );
//      // Get all the tags to merge with the new ones.
//      foreach ($merged_tags as $merged_tag) {
//        $tags[] = $merged_tag;
//      }
//      $build['#cache']['tags'] = $tags;
//
//      $renderer->addCacheableDependency($build, $membership);
    }

    return $build;
  }

}
