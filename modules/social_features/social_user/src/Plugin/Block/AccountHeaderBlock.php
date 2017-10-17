<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Block\BlockBase;
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ActivityNotifications $activity_notifications, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->activityNotifications = $activity_notifications;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $account = $this->getContextValue('user');

    if ($account->id() !== 0) {
      $account_name = $account->getAccountName();

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
        'groups' => [
          'classes' => '',
          'link_attributes' => '',
          'icon_classes' => 'icon-group',
          'title' => $this->t('My Groups'),
          'label' => $this->t('My Groups'),
          'title_classes' => 'sr-only',
          'url' => Url::fromRoute('view.groups.page_user_groups', [
            'user' => $account->id(),
          ]),
        ],
      ];

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
          'classes' => 'dropdown notification-bell',
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

      $storage = $this->entityTypeManager->getStorage('profile');
      $profile = $storage->loadByUser($account, 'profile');

      if ($profile) {
        $content = $this->entityTypeManager
          ->getViewBuilder('profile')
          ->view($profile, 'small');
        $links['account_box']['icon_image'] = $content;
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
