<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class AccountHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The activity notifications.
   *
   * @var \Drupal\activity_creator\ActivityNotifications
   */
  protected $activityNotifications;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AccountHeaderBlock constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\activity_creator\ActivityNotifications $activity_notifications
   *   The activity creator, activity notifications.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config Factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ActivityNotifications $activity_notifications, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->activityNotifications = $activity_notifications;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('activity_creator.activity_notifications'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = $this->getContextValue('user');
    $navigation_settings_config = $this->configFactory->get('social_user.navigation.settings');

    if ($account->id() !== 0) {
      $account_name = $account->getDisplayName();

      $links = [
        'add' => [
          'classes' => 'dropdown',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => 'icon-add_box',
          'title' => $this->t('Create New Content'),
          'label' => $this->t('New content'),
          'title_classes' => 'sr-only',
          'url' => '#',
          'below' => [
            'add_event' => [
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
            ],
            'add_topic' => [
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
            ],
            'add_group' => [
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => $this->t('Create New Group'),
              'label' => $this->t('New group'),
              'title_classes' => '',
              'url' => Url::fromRoute('entity.group.add_page'),
            ],
          ],
        ],
      ];

      if ($this->moduleHandler->moduleExists('social_group')) {
        if ($navigation_settings_config->get('display_my_groups_icon') === 1) {
          $links['groups'] = [
            'classes' => 'desktop',
            'link_attributes' => '',
            'icon_classes' => 'icon-group',
            'title' => $this->t('My Groups'),
            'label' => $this->t('My Groups'),
            'title_classes' => 'sr-only',
            'url' => Url::fromRoute('view.groups.page_user_groups', [
              'user' => $account->id(),
            ]),
          ];
        }
      }

      if ($this->moduleHandler->moduleExists('social_private_message')) {
        if ($navigation_settings_config->get('display_social_private_message_icon') === 1) {
          // Fetch the amount of unread items.
          $num_account_messages = \Drupal::service('social_private_message.service')->updateUnreadCount();

          // Default icon values.
          $message_icon = 'icon-mail_outline';
          $label_classes = 'hidden';
          // Override icons when there are unread items.
          if ($num_account_messages > 0) {
            $message_icon = 'icon-mail';
            $label_classes = 'badge badge-accent badge--pill';
          }

          $links['messages'] = [
            'classes' => 'desktop',
            'link_attributes' => '',
            'icon_classes' => $message_icon,
            'title' => $this->t('Inbox'),
            'label' => (string) $num_account_messages,
            'title_classes' => $label_classes,
            'url' => Url::fromRoute('social_private_message.inbox'),
          ];
        }
      }

      // Check if the current user is allowed to create new books.
      if ($this->moduleHandler->moduleExists('social_book')) {
        $links['add']['below']['add_book'] = [
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
        ];
      }

      // Check if the current user is allowed to create new pages.
      if ($this->moduleHandler->moduleExists('social_page')) {
        $links['add']['below']['add_page'] = [
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
        ];
      }

      // Check if the current user is allowed to create new landing pages.
      if ($this->moduleHandler->moduleExists('social_landing_page')) {
        $links['add']['below']['add_landing_page'] = [
          'classes' => '',
          'link_attributes' => '',
          'link_classes' => '',
          'icon_classes' => '',
          'icon_label' => '',
          'title' => $this->t('Create New Landing Page'),
          'label' => $this->t('New landing page'),
          'title_classes' => '',
          'url' => Url::fromRoute('node.add', [
            'node_type' => 'landing_page',
          ]),
        ];
      }

      if ($this->moduleHandler->moduleExists('activity_creator')) {
        $notifications_view = views_embed_view('activity_stream_notifications', 'block_1');
        $notifications = $this->renderer->render($notifications_view);

        $account_notifications = $this->activityNotifications;
        $num_notifications = count($account_notifications->getNotifications($account, [ACTIVITY_STATUS_RECEIVED]));

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

        $links['notifications'] = [
          'classes' => 'dropdown notification-bell desktop',
          'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
          'link_classes' => 'dropdown-toggle clearfix',
          'icon_classes' => $notifications_icon,
          'title' => $this->t('Notification Centre'),
          'label' => (string) $num_notifications,
          'title_classes' => $label_classes,
          'url' => '#',
          'below' => $notifications,
        ];
      }

      $links['account_box'] = [
        'classes' => 'dropdown profile',
        'link_attributes' => 'data-toggle=dropdown aria-expanded=true aria-haspopup=true role=button',
        'link_classes' => 'dropdown-toggle clearfix',
        'icon_classes' => 'icon-account_circle',
        'title' => $this->t('Profile of @account', ['@account' => $account_name]),
        'label' => $account_name,
        'title_classes' => 'sr-only',
        'url' => '#',
        'below' => [
          'signed_in_as' => [
            'classes' => 'dropdown-header header-nav-current-user',
            'tagline' => $this->t('Signed in as'),
            'object'  => $account_name,
          ],
          'divide_mobile' => [
            'divider' => 'true',
            'classes' => 'divider mobile',
            'attributes' => 'role=separator',
          ],
          'messages_mobile' => [],
          'notification_mobile' => [],
          'divide_profile' => [
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ],
          'my_profile' => [
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('View my profile'),
            'label' => $this->t('My profile'),
            'title_classes' => '',
            'url' => Url::fromRoute('user.page'),
          ],
          'my_events' => [
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
          ],
          'my_topics' => [
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
          ],
          'my_groups' => [
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
          ],
          'divide_content' => [
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ],
          'my_content' => [
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t("View content I'm following"),
            'label' => $this->t('Following'),
            'title_classes' => '',
            'url' => Url::fromRoute('view.following.following'),
          ],
          'divide_account' => [
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ],
          'my_account' => [
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('Settings'),
            'label' => $this->t('Settings'),
            'title_classes' => '',
            'url' => Url::fromRoute('entity.user.edit_form', [
              'user' => $account->id(),
            ]),
          ],
          'edit_profile' => [
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('Edit profile'),
            'label' => $this->t('Edit profile'),
            'title_classes' => '',
            'url' => Url::fromRoute('entity.profile.type.user_profile_form', [
              'user' => $account->id(),
              'profile_type' => 'profile',
            ]),
            'access' => $account->hasPermission('add own profile profile') || $account->hasPermission('bypass profile access'),
          ],
          'divide_logout' => [
            'divider' => 'true',
            'classes' => 'divider',
            'attributes' => 'role=separator',
          ],
          'logout' => [
            'classes' => '',
            'link_attributes' => '',
            'link_classes' => '',
            'icon_classes' => '',
            'icon_label' => '',
            'title' => $this->t('Logout'),
            'label' => $this->t('Logout'),
            'title_classes' => '',
            'url' => Url::fromRoute('user.logout'),
          ],
        ],
      ];
      if ($this->moduleHandler->moduleExists('social_private_message')) {
        if ($navigation_settings_config->get('display_social_private_message_icon') === 1) {
          // Fetch the amount of unread items.
          $num_account_messages = \Drupal::service('social_private_message.service')->updateUnreadCount();

          // Default icon values.
          $label_classes = 'hidden';
          // Override icons when there are unread items.
          if ($num_account_messages > 0) {
            $label_classes = 'badge badge-accent badge--pill';
            $links['account_box']['classes'] = $links['account_box']['classes'] . ' has-alert';
          }
          $links['account_box']['below']['messages_mobile'] = [
            'classes' => 'mobile',
            'link_attributes' => '',
            'icon_classes' => '',
            'title' => $this->t('Inbox'),
            'label' => $this->t('Inbox'),
            'title_classes' => '',
            'count_classes' => $label_classes,
            'count_icon' => (string) $num_account_messages,
            'url' => Url::fromRoute('social_private_message.inbox'),
          ];
        }
      }

      if ($this->moduleHandler->moduleExists('activity_creator')) {
        $account_notifications = $this->activityNotifications;
        $num_notifications = count($account_notifications->getNotifications($account, [ACTIVITY_STATUS_RECEIVED]));

        if ($num_notifications === 0) {
          $label_classes = 'hidden';
        }
        else {
          $label_classes = 'badge badge-accent badge--pill';
          $links['account_box']['classes'] = $links['account_box']['classes'] . ' has-alert';

          if ($num_notifications > 99) {
            $num_notifications = '99+';
          }
        }

        $links['account_box']['below']['notification_mobile'] = [
          'classes' => 'mobile notification-bell',
          'link_attributes' => '',
          'icon_classes' => '',
          'title' => $this->t('Notification Centre'),
          'label' => $this->t('Notification Centre'),
          'title_classes' => '',
          'count_classes' => $label_classes,
          'count_icon' => (string) $num_notifications,
          'url' => Url::fromRoute('view.activity_stream_notifications.page_1'),
        ];
      }

      $storage = $this->entityTypeManager->getStorage('profile');
      $profile = $storage->loadByUser($account, 'profile');

      if ($profile) {
        $content = $this->entityTypeManager
          ->getViewBuilder('profile')
          ->view($profile, 'small');
        $links['account_box']['icon_image'] = $content;
      }

      $hook = 'social_user_account_header_links';

      $divider = [
        'divider' => 'true',
        'classes' => 'divider',
        'attributes' => 'role=separator',
      ];

      foreach ($this->moduleHandler->invokeAll($hook) as $key => $item) {
        if (!isset($links['account_box']['below'][$item['after']]) || isset($links['account_box']['below'][$key])) {
          continue;
        }

        $list = $links['account_box']['below'];

        $links['account_box']['below'] = [];

        foreach ($list as $exist_key => $exist_item) {
          $links['account_box']['below'][$exist_key] = $exist_item;

          if ($item['after'] == $exist_key) {
            if (isset($item['divider']) && $item['divider'] == 'before') {
              $links['account_box']['below'][$key . '_divider'] = $divider;
            }

            $links['account_box']['below'][$key] = [
              'classes' => '',
              'link_attributes' => '',
              'link_classes' => '',
              'icon_classes' => '',
              'icon_label' => '',
              'title' => $item['title'],
              'label' => $item['title'],
              'title_classes' => '',
              'url' => $item['url'],
            ];

            if (isset($item['divider']) && $item['divider'] == 'after') {
              $links['account_box']['below'][$key . '_divider'] = $divider;
            }
          }
        }
      }
    }
    else {
      $links = [
        'home' => [
          'classes' => 'hidden-xs',
          'link_attributes' => '',
          'icon_classes' => '',
          'icon_label' => 'Home',
          'title' => $this->t('Home'),
          'label' => $this->t('Home'),
          'title_classes' => '',
          'url' => Url::fromRoute('<front>'),
        ],
      ];
    }

    foreach (['add', 'account_box'] as $key) {
      if (!isset($links[$key]['below'])) {
        continue;
      }

      foreach ($links[$key]['below'] as &$item) {
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
