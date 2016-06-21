<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'GroupAddTopicBlock' block.
 *
 * @Block(
 *  id = "group_add_topic_block",
 *  admin_label = @Translation("Group add topic block"),
 * )
 */
class GroupAddTopicBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $group_id = \Drupal::routeMatch()->getParameter('group');

    if (!empty($group_id)) {
      $url = Url::fromUserInput("/group/$group_id/node/create/topic");

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

      $build['content'] = Link::fromTextAndUrl(t('Create Topic'), $url)->toRenderable();

      // @TODO Fix cache tags!
      // Disable cache for this block to get correct group_id in path
      $build['#cache'] = array(
        'max-age' => 0,
      );
    }

    return $build;
  }

}
