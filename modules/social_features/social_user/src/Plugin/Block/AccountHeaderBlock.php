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
      $account_name = $account->getDisplayName();

      $links = [
        'add' => array(
          'classes' => 'hidden-mobile',
          'link_attributes' => '',
          'icon_classes' => '',
          'icon_label' => 'Add',
          'label' => 'Add Content',
          'label_classes' => 'hidden',
          'url' => Url::fromRoute('node.add_page'),
        ),
        'home' => array(
          'classes' => 'hidden-mobile',
          'link_attributes' => '',
          'icon_classes' => 'hidden-tablet',
          'icon_label' => 'Home',
          'label' => 'Home',
          'label_classes' => 'hidden-mobile',
          'url' => Url::fromRoute('<front>'),
        ),
        'groups' => array(
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'hidden-tablet',
          'icon_label' => 'Home',
          'label' => 'Groups',
          'label_classes' => 'hidden-mobile',
          'url' => Url::fromRoute('<front>'),
        ),
        'notifications' => array(
          'classes' => '',
          'link_attributes' => 'data-panel="#notification-panel"',
          'link_classes' => 'panel-trigger',
          'icon_classes' => '',
          // the following changes based on whether the user has notifications or not
          'icon_label' => 'notifications_none',
          'label' => 'Notifications',
          'label_classes' => 'hidden',
          'url' => Url::fromRoute('<front>'),
        ),
        'account_box' => array(
          'classes' => '',
          'link_attributes' => 'data-panel="#user-panel"',
          'link_classes' => 'panel-trigger',
          'icon_classes' => '',
          'icon_label' => 'account_box',
          'label' => $account_name,
          'label_classes' => 'hidden-mobile',
          'url' => '#',
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
