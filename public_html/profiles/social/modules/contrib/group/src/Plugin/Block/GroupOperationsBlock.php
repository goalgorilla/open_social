<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Block\GroupOperationsBlock.
 */

namespace Drupal\group\Plugin\Block;

use Drupal\group\Cache\Context\GroupTypeCacheContext;
use Drupal\group\Cache\Context\GroupMembershipCacheContext;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with operations the user can perform on a group.
 *
 * @Block(
 *   id = "group_operations",
 *   admin_label = @Translation("Group operations"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class GroupOperationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This block varies per group type and per current user's group membership
    // permissions. Different group types could have different content plugins
    // enabled, influencing which group operations are available to them. The
    // active user's group permissions define which actions are accessible.
    //
    // We do not need to specify the current user or group as cache contexts
    // because, in essence, a group membership is a union of both.
    $build['#cache']['contexts'] = ['group.type', 'group_membership.permissions'];

    // Of special note is the cache context 'group_membership'. Where the above
    // cache contexts should suffice if everything is ran through the permission
    // system, group operations are an exception. Some operations such as 'join'
    // and 'leave' not only check for a permission, but also the existence of a
    // group membership.
    $build['#cache']['contexts'][] = 'group_membership';

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if ($group = $this->getContextValue('group')) {
      $links = [];
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        $links += $plugin->getGroupOperations($group);
      }

      if ($links) {
        uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

        // Instead of copying the cache tag logic from the cache contexts, we
        // run the existing code to generate cache tags for us. Hopefully, core
        // will fix this. See: https://www.drupal.org/node/2666838.
        $service_1 = \Drupal::service('group.group_route_context');
        $service_2 = \Drupal::service('current_user');
        $context_1 = new GroupTypeCacheContext($service_1, $service_2);
        $context_2 = new GroupMembershipCacheContext($service_1, $service_2);
        $tags = Cache::mergeTags(
          $context_1->getCacheableMetadata()->getCacheTags(),
          $context_2->getCacheableMetadata()->getCacheTags()
        );

        $build['#type'] = 'operations';
        $build['#cache']['tags'] = $tags;
        $build['#links'] = $links;
      }
    }

    // If no group was found, cache the empty result on the route.
    return $build;
  }

}
