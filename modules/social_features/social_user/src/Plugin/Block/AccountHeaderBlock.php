<?php

namespace Drupal\social_user\Plugin\Block;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'AccountHeaderBlock' block.
 *
 * @Block(
 *   id = "account_header_block",
 *   admin_label = @Translation("Account header block"),
 *   context_definitions = {
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
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

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
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, RendererInterface $renderer, ActivityNotifications $activity_notifications, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, ControllerResolverInterface $controller_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->activityNotifications = $activity_notifications;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->controllerResolver = $controller_resolver;
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
      $container->get('config.factory'),
      $container->get('controller_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // This context is used to pass the block context to hooks.
    $context = $this->getContextValues();

    $block = [
      '#attributes' => [
        'class' => ['navbar-user'],
      ],
      'menu_items' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => ['nav', 'navbar-nav'],
        ],
        '#items' => [],
      ],
    ];

    // Create a convenience shortcut for later code.
    $menu_items = &$block['menu_items']['#items'];

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->getContextValue('user');

    if ($account->isAuthenticated()) {
      $menu_items['create'] = [
        '#type' => 'account_header_element',
        '#title' => $this->t('Create New Content'),
        '#url' => Url::fromRoute('<none>'),
        '#icon' => 'add_box',
        '#label' => $this->t('New Content'),
      ];

      // Gather the content creation links from all modules.
      // Weights can be used to order the subitems of an account_header_element.
      $create_links = $this->moduleHandler->invokeAll('social_user_account_header_create_links', [$context]);

      // Allow modules to alter the defined content creation links.
      $this->moduleHandler->alter('social_user_account_header_create_links', $create_links, $context);

      $menu_items['create'] += $create_links;

      // If the user can't access any children then we disable the entire menu.
      if (isset($menu_items['create']) && is_array($menu_items['create']) && !$this->hasAccessibleChild($menu_items['create'])) {
        $menu_items['create']['#access'] = FALSE;
      }

      $account_name = $account->getDisplayName();

      $menu_items['account_box'] = [
        '#type' => 'account_header_element',
        '#wrapper_attributes' => [
          'class' => ['profile'],
        ],
        '#icon' => 'account_circle',
        '#title' => $this->t('Profile of @account', ['@account' => $account_name]),
        '#label' => $account_name,
        '#url' => Url::fromRoute('<none>'),
        '#weight' => 1000,
      ];

      $account_links = [
        'signed_in_as' => [
          '#wrapper_attributes' => [
            'class' => ['dropdown-header', 'header-nav-current-user'],
          ],
          '#type' => 'inline_template',
          '#template' => '<a href="/user">{{ tagline }}<strong class="text-truncate">{{ object }} </strong></a>',
          '#context' => [
            'tagline' => $this->t('Signed in as'),
            'object'  => $account_name,
          ],
          '#weight' => 0,
        ],
        // @todo Figure out how move these dividers to the right modules.
        'divider_mobile' => [
          "#wrapper_attributes" => [
            "class" => ["divider", "mobile"],
            "role" => "separator",
          ],
          '#weight' => 100,
        ],
        'divider_profile' => [
          "#wrapper_attributes" => [
            "class" => ["divider"],
            "role" => "separator",
          ],
          '#weight' => 400,
        ],

        'divider_content' => [
          "#wrapper_attributes" => [
            "class" => ["divider"],
            "role" => "separator",
          ],
          '#weight' => 900,
        ],
        'divider_account' => [
          "#wrapper_attributes" => [
            "class" => ["divider"],
            "role" => "separator",
          ],
          '#weight' => 1100,
        ],
      ];

      // Gather the account related links from extending modules.
      $account_links += $this->moduleHandler->invokeAll('social_user_account_header_account_links', [$context]);

      // Allow modules to alter the defined account related links.
      $this->moduleHandler->alter('social_user_account_header_account_links', $account_links, $context);

      // Append the account links as children to the account menu.
      $menu_items['account_box'] += $account_links;
    }

    // We allow modules to add their items to the account header block.
    // We use the union operator (+) to ensure modules can't overwrite items
    // defined above. They should use the alter hook for that.
    $menu_items += $this->moduleHandler->invokeAll('social_user_account_header_items', [$context]);

    // Allow modules to alter the defined account header block items.
    $this->moduleHandler->alter('social_user_account_header_items', $menu_items, $context);

    // We render this element as an item_list (template_preprocess_item_list)
    // which doesn't sort. Therefore it happens here.
    Element::children($menu_items, TRUE);

    // We remove the '#sorted' key that's added above because
    // template_preprocess_item_list does not know how to handle it.
    unset($menu_items['#sorted']);

    // The item_list theme function gets the wrapper_attributes before the
    // AccountHeaderElement::preRender is called. Therefor we provide some
    // assisting classes here.
    foreach ($menu_items as &$item) {
      if (empty($item['#type']) || $item['#type'] !== 'account_header_element') {
        continue;
      }

      // Sort the children according to their weight.
      $children = Element::children($item, TRUE);

      // If this element has children then it's a dropdown.
      if (!empty($children)) {
        if (empty($item['#wrapper_attributes'])) {
          $item['#wrapper_attributes'] = [];
        }

        if (empty($item['#wrapper_attributes']['class'])) {
          $item['#wrapper_attributes']['class'] = [];
        }

        $item['#wrapper_attributes']['class'][] = 'dropdown';
      }
    }

    return $block;
  }

  /**
   * Checks if the element has a child with a valid access check.
   *
   * Loops through the children until it finds an element whose access is set to
   * true or who has an access check returning true.
   *
   * This method is needed in case the parent creates markup that should be
   * hidden if the user can't access any of the children.
   *
   * This method mutates the checked children and sets their #access values so
   * that the access checks don't have to happen multiple times.
   *
   * @param array $parent
   *   The parent element whose children to check.
   *
   * @return bool
   *   Whether the parent has visible children.
   */
  protected function hasAccessibleChild(array &$parent) : bool {
    $children = Element::children($parent);

    foreach ($children as $key) {
      $element = &$parent[$key];
      // If there's no precomputed access value, check for access callbacks.
      // This is logic adapted from Drupal\Core\Render\Renderer::doRender.
      if (!empty($element) && !isset($element['#access']) && isset($element['#access_callback'])) {
        if (is_string($element['#access_callback']) && strpos($element['#access_callback'], '::') === FALSE) {
          $element['#access_callback'] = $this->controllerResolver->getControllerFromDefinition($element['#access_callback']);
        }
        // Store the result of the access callback so it can be checked.
        $element['#access'] = call_user_func($element['#access_callback'], $element);
      }

      // Check if there's a precomputed access value or if one has been set
      // from an access callback.
      if (isset($element['#access'])) {
        // It can either be an access result or a true/false value.
        if ($element['#access'] instanceof AccessResultInterface) {
          // If this child is inaccessible then we check the other children.
          if ($element['#access']->isForbidden()) {
            continue;
          }
          // If the child is not forbidden then we've found an accessible child.
          return TRUE;
        }

        // If the access variable is falsy then check the other children.
        if (!$element['#access']) {
          continue;
        }
        // A truthy access value has been found so we have an accessible child.
        return TRUE;
      }

      // If this element has no access checks then it's visible.
      return TRUE;
    }

    // No accessible child was found.
    return FALSE;
  }

}
