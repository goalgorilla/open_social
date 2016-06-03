<?php

/**
 * @file
 * Contains \Drupal\social_group\Plugin\Block\GroupAddEventBlock.
 */

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'GroupAddEventBlock' block.
 *
 * @Block(
 *  id = "group_add_event_block",
 *  admin_label = @Translation("Group add event block"),
 * )
 */
class GroupAddEventBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $group_id = \Drupal::routeMatch()->getParameter('group');

    if(!empty($group_id)){
      $url = Url::fromUserInput("/group/$group_id/node/create/event");

      $link_options = array(
        'attributes' => array(
          'class' => array(
            'btn',
            'btn-primary',
            'btn-raised',
            'btn-block',
            'waves-effect',
            'waves-light',
          ),
        ),
      );
      $url->setOptions($link_options);

      $build['content'] = Link::fromTextAndUrl(t('Create Event'), $url)->toRenderable();
      // Disable cache for this block to get correct group_id in path
      $build['#cache'] = array(
        'max-age' => 0,
      );
    }

    return $build;
  }

}
