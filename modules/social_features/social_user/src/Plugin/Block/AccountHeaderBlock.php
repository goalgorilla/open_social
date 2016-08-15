<?php

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
          'classes' => 'dropdown',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => 'icon-add_box',
          'title' => 'Add content',
          'title_classes' => 'sr-only',
          'url' => '#',
          'below' => array(
            'add_event' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => 'New Event',
              'title_classes' => '',
              'url' => '/node/add/event',
            ),
            'add_topic' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => 'New Topic',
              'title_classes' => '',
              'url' => '/node/add/topic',
            ),
            'add_group' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => 'New Group',
              'title_classes' => '',
              'url' => '/group/add/open_group',
            ),
          ),
        ),
        'groups' => array(
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'icon-group',
          'title' => 'My groups',
          'title_classes' => 'sr-only',
          'url' => '/user/' . $account_uid . '/groups',
        ),
      ];

      if (\Drupal::moduleHandler()->moduleExists('activity_creator')) {
        $notifications_view = views_embed_view('activity_stream_notifications', 'block_1');
        $notifications = \Drupal::service('renderer')->render($notifications_view);

        $account_notifications = \Drupal::service('activity_creator.activity_notifications');
        $num_notifications = count($account_notifications->getNotifications($account, array(ACTIVITY_STATUS_RECEIVED)));


        if ($num_notifications === 0) {
          $notifications_icon = 'icon-notifications_none';
          $label_classes = 'hidden';
        }
        else {
          $notifications_icon = 'icon-notifications';
          $label_classes = 'badge';

          if ($num_notifications > 99) {
            $num_notifications = '99+';
          }
        }

        $links['notifications'] = array(
          'classes' => 'dropdown notification-bell',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => $notifications_icon,
          'title' => (string) $num_notifications,
          'title_classes' => $label_classes,
          'url' => '#',
          'below' => $notifications,
        );
      }

      $links['account_box'] = array(
        'classes' => 'dropdown profile',
        'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
        'link_classes' => 'dropdown-toggle clearfix',
        'icon_classes' => 'icon-account_circle',
        'title' => $account_name,
        'title_classes' => 'sr-only',
        'url' => '#',
        'below' => array(
          'signed_in_as' => array(
            'classes' => 'dropdown-header header-nav-current-user',
            'tagline' => 'Signed in as',
            'object'  => $account_name,
          ),
          'divide_profile' => array(
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ),
          'my_profile' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'My profile',
            'title_classes' => '',
            'url' => '/user',
          ),
          'my_events' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'My events',
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/events',
          ),
          'my_topics' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'My topics',
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/topics',
          ),
          'my_groups' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'My groups',
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/groups',
          ),
          'divide_account' => array(
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ),
          'my_account' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'Edit account',
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/edit',
          ),
          'edit_profile' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'Edit profile',
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/profile',
          ),
          'divide_logout' => array(
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ),
          'logout' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => 'Logout',
            'title_classes' => '',
            'url' => '/user/logout',
          ),
        ),
      );
    }
    else {
      $links = [
        'home' => array(
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => '',
          'icon_label' => 'Home',
          'title' => 'Home',
          'title_classes' => '',
          'url' => Url::fromRoute('<front>'),
        ),
      ];
    }

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => array(
        'contexts' => array('user'),
      ),
      '#attached' => array(
        'library' => array(
          'activity_creator/activity_creator.notifications',
        ),
      ),
    ];
  }

}
