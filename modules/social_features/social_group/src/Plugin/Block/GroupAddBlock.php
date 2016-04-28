<?php

/**
 * @file
 * Contains \Drupal\social_group\Plugin\Block\GroupAddBlock.
 */

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'GroupAddBlock' block.
 *
 * @Block(
 *  id = "group_add_block",
 *  admin_label = @Translation("Group add block"),
 * )
 */
class GroupAddBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $url = Url::fromUserInput('/group/add/open_group');

    $link_options = array(
      'attributes' => array(
        'class' => array(
          'btn',
          'btn-primary',
        ),
      ),
    );
    $url->setOptions($link_options);

    $build['group_add_block'] = Link::fromTextAndUrl(t('Add a Group'), $url)->toRenderable();

    return $build;
  }

}
