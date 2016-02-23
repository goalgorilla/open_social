<?php

/**
 * @file
 * Contains \Drupal\social_user\Plugin\Block\AccountHeaderBlock.
 */

namespace Drupal\social_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'AccountHeaderBlock' block.
 *
 * @Block(
 *  id = "account_header_block",
 *  admin_label = @Translation("Account header block"),
 * )
 */
class AccountHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = \Drupal::currentUser();
    if ($account->id() !== 0) {
      $account_name = $account->getAccountName();

      $links = [
        'add' => array(
          'classes' => 'menu__item hidden-mobile',
          'label' => 'Add',
          'url' => Url::fromRoute('node.add_page'),
        ),
        'home' => array(
          'classes' => 'menu__item hidden-mobile',
          'label' => 'Home',
          'url' => Url::fromRoute('<front>'),
        ),
        'groups' => array(
          'classes' => 'menu__item hidden-mobile',
          'label' => 'Groups',
          'url' => Url::fromRoute('<front>'),
        ),
        'notifications' => array(
          'classes' => 'menu__item hidden-mobile',
          'label' => 'Notifications',
          'url' => Url::fromRoute('<front>'),
        ),
        'account_box' => array(
          'classes' => 'menu__item hidden-mobile',
          'label' => $account_name,
          'url' => Url::fromRoute('user.page'),
        ),
      ];
    }
    else {
      $links = [

      ];
    }

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => array(
         'contexts' => array('user'),
      ),
    ];
  }

}
