<?php

namespace Drupal\social_admin_menu\Menu;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\toolbar\Menu\ToolbarMenuLinkTree;

/**
 * Provides a couple of menu link tree manipulators.
 *
 * This class provides menu link tree manipulators to:
 * - perform render cached menu-optimized access checking
 * - optimized node access checking
 * - generate a unique index for the elements in a tree and sorting by it
 * - flatten a tree (i.e. a 1-dimensional tree)
 */
class SocialAdminMenuAdministratorMenuLinkTreeManipulators extends DefaultMenuLinkTreeManipulators implements TrustedCallbackInterface {

  /**
   * The toolbar menu link tree.
   *
   * @var \Drupal\toolbar\Menu\ToolbarMenuLinkTree
   */
  protected $toolbarMenuLinkTree;

  /**
   * Constructs a \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators object.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\toolbar\Menu\ToolbarMenuLinkTree $toolnar_menu_link_tree
   *   The toolbar menu link tree.
   */
  public function __construct(AccessManagerInterface $access_manager, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, ToolbarMenuLinkTree $toolnar_menu_link_tree) {
    parent::__construct($access_manager, $account, $entity_type_manager);

    $this->toolbarMenuLinkTree = $toolnar_menu_link_tree;
  }

  /**
   * Performs access checks of a menu tree.
   *
   * Sets the 'access' property to AccessResultInterface objects on menu link
   * tree elements. Descends into subtrees if the root of the subtree is
   * accessible. Inaccessible subtrees are deleted, except the top-level
   * inaccessible link, to be compatible with render caching.
   *
   * (This means that top-level inaccessible links are *not* removed; it is up
   * to the code doing something with the tree to exclude inaccessible links,
   * just like MenuLinkTree::build() does. This allows those things to specify
   * the necessary cacheability metadata.)
   *
   * This is compatible with render caching, because of cache context bubbling:
   * conditionally defined cache contexts (i.e. subtrees that are only
   * accessible to some users) will bubble just like they do for render arrays.
   * This is why inaccessible subtrees are deleted, except at the top-level
   * inaccessible link: if we didn't keep the first (depth-wise) inaccessible
   * link, we wouldn't be able to know which cache contexts would cause those
   * subtrees to become accessible again, thus forcing us to conclude that that
   * subtree is unconditionally inaccessible.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function checkAccess(array $tree) {

    if ($this->account->id() != 1) {
      $account_roles = $this->account->getRoles();
      // Define routes to hide for a role.
      // 'content' => 'system.admin_content',
      // 'structure' => 'system.admin_structure',
      // 'config' => 'system.admin_config',
      // 'reports' => 'system.admin_reports',
      // 'themes' => 'system.themes_page',
      // 'people' => 'entity.user.collection',
      // 'help' => 'help.main'.
      $routes = [
        'contentmanager' => [
          'config' => 'system.admin_config',
          'reports' => 'system.admin_reports',
          'help' => 'help.main',
        ],
        'sitemanager' => [
          'help' => 'help.main',
        ],
      ];

      // Define which routes to hide based on hierarchy.
      if (in_array('sitemanager', $account_roles)) {
        $hide_routes = $routes['sitemanager'];
      }
      elseif (in_array('contentmanager', $account_roles)) {
        $hide_routes = $routes['contentmanager'];
      }
      else {
        $hide_routes = [];
      }

      foreach ($tree as $key => $element) {
        // Always hide the admin_toolbar_tools.help.
        $plugin_id = $tree[$key]->link->getPluginId();
        if ($plugin_id === 'admin_toolbar_tools.help') {
          unset($tree[$key]);
          continue;
        }

        $route = $tree[$key]->link->getRouteName();
        if (in_array($route, $hide_routes)) {
          unset($tree[$key]);
          continue;
        }

      }
    }

    return $tree;
  }

  /**
   * Renders the toolbar's administration tray.
   *
   * This is a adoption of core's
   * toolbar_prerender_toolbar_administration_tray() function,
   * which uses setMaxDepth(4) instead of setTopLevelOnly()
   *
   * @return array
   *   The updated renderable array.
   *
   * @see admin_toolbar_prerender_toolbar_administration_tray()
   */
  public function renderForm() {
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('system.admin')
      ->excludeRoot()
      ->setMaxDepth(4)
      ->onlyEnabledLinks();
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'toolbar_tools_menu_navigation_links'],
      ['callable' => 'social_admin_menu.administrator_menu_tree_manipulators:checkAccess'],
    ];
    $tree = $this->toolbarMenuLinkTree->load(NULL, $parameters);
    $tree = $this->toolbarMenuLinkTree->transform($tree, $manipulators);
    $element['toolbar_administration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['toolbar-menu-administration'],
      ],
      '#cache' => [
        'contexts' => [
          'user.roles',
        ],
      ],
      'administration_menu' => $this->toolbarMenuLinkTree->build($tree),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderForm'];
  }

}
