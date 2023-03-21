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
    $parameters->setRoot('social_core.dashboard')
      ->excludeRoot()
      ->setMaxDepth(2)
      ->onlyEnabledLinks();
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'gin_toolbar_tools_menu_navigation_links'],
    ];
    $tree = $this->toolbarMenuLinkTree->load('social_core.dashboard', $parameters);
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
