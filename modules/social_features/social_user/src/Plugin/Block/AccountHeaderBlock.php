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
      $account_uid = $account->id();

      $links = [
        'add' => array(
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => '',
          'icon_label' => 'Add',
          'label' => 'Add Content',
          'label_classes' => 'hidden',
          'url' => Url::fromRoute('node.add_page'),
        ),
        'home' => array(
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => 'hidden-sm hidden-md hidden-lg ',
          'icon_label' => 'Home',
          'label' => 'Home',
          'label_classes' => '',
          'url' => Url::fromRoute('<front>'),
        ),
        'groups' => array(
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'hidden-sm hidden-md hidden-lg ',
          'icon_label' => 'Group',
          'label' => 'Groups',
          'label_classes' => 'hidden-xs',
          'url' => Url::fromRoute('<front>'),
        ),
        'notifications' => array(
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          // the following changes based on whether the user has notifications or not
          'icon_label' => 'notifications_none',
          'label' => 'Notifications',
          'label_classes' => 'hidden',
          'url' => Url::fromRoute('<front>'),
        ),
        'account_box' => array(
          'classes' => '',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle',
          'icon_classes' => '',
          'icon_label' => 'account_box',
          'label' => $account_name,
          'label_classes' => 'hidden-xs',
          'url' => '#',
          'below' => array(
            'my_profile' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'label' => 'View profile',
              'label_classes' => '',
              'url' => '/user',
            ),
            'my_account' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'label' => 'Edit account',
              'label_classes' => '',
              'url' => '/user/' . $account_uid . '/edit',
            ),
            'edit_profile' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'label' => 'Edit profile',
              'label_classes' => '',
              'url' => '/user/' . $account_uid . '/profile',
            ),
            'logout' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'label' => 'Logout',
              'label_classes' => '',
              'url' => '/user/logout',
            ),
          ),
        ),
      ];
    }
    else {
      $links = [
        'home' => array(
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => 'hidden-sm hidden-md hidden-lg',
          'icon_label' => 'Home',
          'label' => 'Home',
          'label_classes' => '',
          'url' => Url::fromRoute('<front>'),
        ),
      ];
    }

    $block = \Drupal\block\Entity\Block::load('search_content_block_header');
    $block_output = \Drupal::entityManager()
      ->getViewBuilder('block')
      ->view($block);

    $links['search_block'] = $block_output;

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => array(
         'contexts' => array('user'),
      ),
    ];
  }

}
