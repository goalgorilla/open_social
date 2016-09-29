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
          'title' => t('Add content'),
          'title_classes' => 'sr-only',
          'url' => '#',
          'below' => array(
            'add_event' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => t('New Event'),
              'title_classes' => '',
              'url' => '/node/add/event',
            ),
            'add_topic' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => t('New Topic'),
              'title_classes' => '',
              'url' => '/node/add/topic',
            ),
            'add_group' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => t('New Group'),
              'title_classes' => '',
              'url' => '/group/add/open_group',
            ),
          ),
        ),
        'groups' => array(
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'icon-group',
          'title' => t('My groups'),
          'title_classes' => 'sr-only',
          'url' => '/user/' . $account_uid . '/groups',
        ),
      ];

      // Check if the current user is allowed to create new books.
      if (\Drupal::moduleHandler()->moduleExists('social_book') && $account->hasPermission('create new books')) {
        $links['add']['below']['add_book'] = array(
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          'icon_label' => '',
          'title' => t('New Book page'),
          'title_classes' => '',
          'url' => '/node/add/book',
        );
      }

      // Check if the current user is allowed to create new pages.
      if (\Drupal::moduleHandler()->moduleExists('social_page') && $account->hasPermission('create page content')) {
        $links['add']['below']['add_page'] = array(
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          'icon_label' => '',
          'title' => t('New Page'),
          'title_classes' => '',
          'url' => '/node/add/page',
        );
      }

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
            'title' => t('My profile'),
            'title_classes' => '',
            'url' => '/user',
          ),
          'my_events' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => t('My events'),
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/events',
          ),
          'my_topics' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => t('My topics'),
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/topics',
          ),
          'my_groups' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => t('My groups'),
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
            'title' => t('Edit account'),
            'title_classes' => '',
            'url' => '/user/' . $account_uid . '/edit',
          ),
          'edit_profile' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => t('Edit profile'),
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
            'title' => t('Logout'),
            'title_classes' => '',
            'url' => '/user/logout',
          ),
        ),
      );

      if ($account) {
        $storage = \Drupal::entityTypeManager()->getStorage('profile');
        if (!empty($storage)) {
          $user_profile = $storage->loadByUser($account, 'profile');
          if ($user_profile) {
            $content = \Drupal::entityTypeManager()
              ->getViewBuilder('profile')
              ->view($user_profile, 'small');
            $links['account_box']['icon_image'] = $content;
          }
        }
      }

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
