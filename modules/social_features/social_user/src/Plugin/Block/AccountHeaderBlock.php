<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'AccountHeaderBlock' block.
 *
 * @Block(
 *   id = "account_header_block",
 *   admin_label = @Translation("Account header block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class AccountHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = $this->getContextValue('user');

    if ($account->id() !== 0) {
      $account_name = $account->getAccountName();

      $links = [
        'add' => array(
          'classes' => 'dropdown',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => 'icon-add_box',
          'title' => $this->t('Create New Content'),
          'label' => $this->t('New content'),
          'title_classes' => 'sr-only',
          'url' => '#',
          'below' => array(
            'add_event' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => $this->t('Create New Event'),
              'label' => $this->t('New event'),
              'title_classes' => '',
              'url' => Url::fromRoute('node.add', [
                'node_type' => 'event',
              ]),
            ),
            'add_topic' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => $this->t('Create New Topic'),
              'label' => $this->t('New topic'),
              'title_classes' => '',
              'url' => Url::fromRoute('node.add', [
                'node_type' => 'topic',
              ]),
            ),
            'add_group' => array(
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => $this->t('Create New Group'),
              'label' => $this->t('New group'),
              'title_classes' => '',
              'url' => Url::fromRoute('entity.group.add_page'),
            ),
          ),
        ),
        'groups' => array(
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'icon-group',
          'title' => $this->t('My Groups'),
          'label' => $this->t('My Groups'),
          'title_classes' => 'sr-only',
          'url' => Url::fromRoute('view.groups.page_user_groups', [
            'user' => $account->id(),
          ]),
        ),
      ];

      // Check if the current user is allowed to create new books.
      if (\Drupal::moduleHandler()->moduleExists('social_book')) {
        $links['add']['below']['add_book'] = array(
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          'icon_label' => '',
          'title' => $this->t('Create New Book page'),
          'label' => $this->t('New book page'),
          'title_classes' => '',
          'url' => Url::fromRoute('node.add', [
            'node_type' => 'book',
          ]),
          'access' => $account->hasPermission('create new books'),
        );
      }

      // Check if the current user is allowed to create new pages.
      if (\Drupal::moduleHandler()->moduleExists('social_page')) {
        $links['add']['below']['add_page'] = array(
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          'icon_label' => '',
          'title' => $this->t('Create New Page'),
          'label' => $this->t('New page'),
          'title_classes' => '',
          'url' => Url::fromRoute('node.add', [
            'node_type' => 'page',
          ]),
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
          $label_classes = 'badge badge-accent badge--pill';

          if ($num_notifications > 99) {
            $num_notifications = '99+';
          }
        }

        $links['notifications'] = array(
          'classes' => 'dropdown notification-bell',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => $notifications_icon,
          'title' => $this->t('Notification Centre'),
          'label' => (string) $num_notifications,
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
        'title' => $this->t('Profile of @account', array('@account' => $account_name)),
        'label' => $account_name,
        'title_classes' => 'sr-only',
        'url' => '#',
        'below' => array(
          'signed_in_as' => array(
            'classes' => 'dropdown-header header-nav-current-user',
            'tagline' => $this->t('Signed in as'),
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
            'title' => $this->t('View my profile'),
            'label' => $this->t('My profile'),
            'title_classes' => '',
            'url' => Url::fromRoute('user.page'),
          ),
          'my_events' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('View my events'),
            'label' => $this->t('My events'),
            'title_classes' => '',
            'url' => Url::fromRoute('view.events.events_overview', [
              'user' => $account->id(),
            ]),
          ),
          'my_topics' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('View my topics'),
            'label' => $this->t('My topics'),
            'title_classes' => '',
            'url' => Url::fromRoute('view.topics.page_profile', [
              'user' => $account->id(),
            ]),
          ),
          'my_groups' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('View my groups'),
            'label' => $this->t('My groups'),
            'title_classes' => '',
            'url' => Url::fromRoute('view.groups.page_user_groups', [
              'user' => $account->id(),
            ]),
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
            'title' => $this->t('Edit account'),
            'label' => $this->t('Edit account'),
            'title_classes' => '',
            'url' => Url::fromRoute('entity.user.edit_form', [
              'user' => $account->id(),
            ]),
          ),
          'edit_profile' => array(
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('Edit profile'),
            'label' => $this->t('Edit profile'),
            'title_classes' => '',
            'url' => Url::fromRoute('entity.profile.type.profile.user_profile_form', [
              'user' => $account->id(),
              'profile_type' => 'profile',
            ]),
            'access' => $account->hasPermission('add own profile profile') || $account->hasPermission('bypass profile access'),
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
            'title' => $this->t('Logout'),
            'label' => $this->t('Logout'),
            'title_classes' => '',
            'url' => Url::fromRoute('user.logout'),
          ),
        ),
      );

      $storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profile = $storage->loadByUser($account, 'profile');

      if ($profile) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($profile, 'small');
        $links['account_box']['icon_image'] = $content;
      }
    }
    else {
      $links = [
        'home' => array(
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => '',
          'icon_label' => 'Home',
          'title' => $this->t('Home'),
          'label' => $this->t('Home'),
          'title_classes' => '',
          'url' => Url::fromRoute('<front>'),
        ),
      ];
    }

    foreach (['add', 'account_box'] as $key) {
      if (!isset($links[ $key ]['below'])) {
        continue;
      }

      foreach ($links[ $key ]['below'] as &$item) {
        if (!isset($item['access']) && isset($item['url']) && $item['url'] instanceof Url) {
          $item['access'] = $item['url']->access($account);
        }
      }
    }

    if (isset($links['groups']['url']) && $links['groups']['url'] instanceof Url) {
      $links['groups']['access'] = $links['groups']['url']->access($account);
    }

    return [
      '#theme' => 'account_header_links',
      '#links' => $links,
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
      '#attached' => [
        'library' => [
          'activity_creator/activity_creator.notifications',
        ],
      ],
    ];
  }

}
