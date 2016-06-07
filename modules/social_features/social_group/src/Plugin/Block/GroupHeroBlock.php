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

    $build['#cache']['tags'][] = 'group_membership:' . $group->id();

    return $build;
  }

}
