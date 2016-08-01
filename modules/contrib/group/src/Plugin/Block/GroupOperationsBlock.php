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
    $build['#cache']['contexts'] = ['group.type', 'group_membership.roles.permissions'];

    // Of special note is the cache context 'group_membership.audience'. Where
    // the above cache contexts should suffice if everything is ran through the
    // permission system, group operations are an exception. Some operations
    // such as 'join' and 'leave' not only check for a permission, but also the
    // audience the user belongs to. I.e.: whether they're a 'member', an
    // 'outsider' or 'anonymous'.
    $build['#cache']['contexts'][] = 'group_membership.audience';

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if (($group = $this->getContextValue('group')) && $group->id()) {
      $links = [];
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        $links += $plugin->getGroupOperations($group);
      }

      if ($links) {
        uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
        $build['#type'] = 'operations';
        // @todo We should have operation links provide cacheable metadata that
        // we could then merge in here.
        $build['#links'] = $links;
      }
    }

    // If no group was found, cache the empty result on the route.
    return $build;
  }

}
