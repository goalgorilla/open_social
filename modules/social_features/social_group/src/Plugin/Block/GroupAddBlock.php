<?php

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

    // @TODO Fix cache tags!
    // Disable cache for this block to get correct user_id from path
    $build['#cache'] = array(
      'max-age' => 0,
    );

    $current_user = \Drupal::currentUser();
    $route_user_id = \Drupal::routeMatch()->getParameter('user');
    // Show this block only on current user Groups page.
    if ($current_user->id() == $route_user_id) {
      $url = Url::fromUserInput('/group/add/open_group');
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

      $build['content'] = Link::fromTextAndUrl(t('Add a group'), $url)
        ->toRenderable();
    }

    return $build;
  }

}
