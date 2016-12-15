<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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

    $group = _social_group_get_current_group();

    if (!empty($group)) {
      // Content
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'hero');

      $build['content'] = $content;
      // Cache tags.
      $build['#cache']['tags'][] = 'group_block:' . $group->id();
    }
    // Cache contexts.
    $build['#cache']['contexts'][] = 'url.path';


    return $build;
  }

}
