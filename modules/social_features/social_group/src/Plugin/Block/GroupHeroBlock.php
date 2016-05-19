<?php

/**
 * @file
 * Contains \Drupal\social_group\Plugin\Block\GroupHeroBlock.
 */

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GroupHeroBlock' block.
 *
 * @Block(
 *  id = "group_hero_block",
 *  admin_label = @Translation("Group hero block"),
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

    dpm($group);

    if (!empty($group)) {
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'hero');

      dpm($content);

      $build['content'] = $content;
    }


    // @TODO make sure it fits for all the use cases.

    $build['#cache'] = array(
      'max-age' => 0,
    );

    return $build;
  }

}
