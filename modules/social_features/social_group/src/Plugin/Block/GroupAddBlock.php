<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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
   *
   * Custom access logic to display the block.
   */
  function blockAccess(AccountInterface $account) {
    $current_user = \Drupal::currentUser();
    $route_user_id = \Drupal::routeMatch()->getParameter('user');

    // Show this block only on current user Groups page.
    if ($current_user->id() == $route_user_id) {
      return AccessResult::allowed();
    }

    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    //@TODO: Change url and add caching when closed groups will be added.
    $url = Url::fromUserInput('/group/add');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'btn',
          'btn-primary',
          'btn-raised',
          'waves-effect',
          'brand-bg-primary',
        ),
      ),
    );
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl(t('Add a group'), $url)
      ->toRenderable();

    return $build;
  }

}
