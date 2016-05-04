<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnabler\GroupMembership.
 */

namespace Drupal\group\Plugin\GroupContentEnabler;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Adds users to groups as members."),
 *   entity_type_id = "user",
 *   path_key = "members",
 *   enforced = TRUE
 * )
 */
class GroupMembership extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $operations = [];

    if ($group->getMember($account)) {
      if ($group->hasPermission('leave group', $account)) {
        $operations['group-leave'] = [
          'title' => $this->t('Leave group'),
          'url' => new Url($this->getRouteName('leave-form'), ['group' => $group->id()]),
          'weight' => 99,
        ];
      }
    }
    elseif ($group->hasPermission('join group', $account)) {
      $operations['group-join'] = [
        'title' => $this->t('Join group'),
        'url' => new Url($this->getRouteName('join-form'), ['group' => $group->id()]),
        'weight' => 0,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityForms() {
    return [
      'group-join' => 'Drupal\group\Form\GroupJoinForm',
      'group-leave' => 'Drupal\group\Form\GroupLeaveForm',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions['administer members'] = [
      'title' => 'Administer group members',
      'description' => 'Administer the group members',
      'restrict access' => TRUE,
    ];

    // @todo This can be removed once plugin permissions are autosorted.
    $permissions += parent::getPermissions();

    $permissions['join group'] = [
      'title' => 'Join group',
      'description' => 'Join a group by filling out the configured fields',
      'allowed for' => ['outsider'],
    ];

    $permissions['leave group'] = [
      'title' => 'Leave group',
      'allowed for' => ['member'],
    ];

    // Update the labels of the default permissions.
    $permissions['access group_membership overview']['title'] = 'Access the member overview page';
    $permissions['view group_membership content']['title'] = 'View individual group members';
    $permissions['edit own group_membership content'] = [
      'title' => 'Edit own membership',
      'allowed for' => ['member'],
    ];

    // These are handled by 'administer members' or 'leave group'.
    unset($permissions['create group_membership content']);
    unset($permissions['edit any group_membership content']);
    unset($permissions['delete any group_membership content']);
    unset($permissions['delete own group_membership content']);

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    return parent::getPaths() + [
      'join-form' => '/group/{group}/join',
      'leave-form' => '/group/{group}/leave',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute() {
    $route = parent::getCollectionRoute();

    // Reset the default requirements and add our own group permissions. The '+'
    // signifies that only one permission needs to be set for the user. We also
    // don't set the _group_installed_content requirement again because we know
    // this plugin will always be installed.
    $route->setRequirements([])->setRequirement('_group_permission', 'administer members+access group_membership overview');

    // Swap out the GroupContent list controller for our own.
    // @todo Implement this after we've completed the above list controller.

    return $route;
  }

  /**
   * Gets the join form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getJoinFormRoute() {
    if ($path = $this->getPath('join-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Controller\GroupMembershipController::join',
          '_title_callback' => '\Drupal\group\Controller\GroupMembershipController::joinTitle',
          'plugin_id' => $this->getPluginId(),
        ])
        ->setRequirement('_group_permission', 'join group')
        ->setRequirement('_group_member', 'FALSE')
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the leave form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getLeaveFormRoute() {
    if ($path = $this->getPath('leave-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Controller\GroupMembershipController::leave',
          'plugin_id' => $this->getPluginId(),
        ])
        ->setRequirement('_group_permission', 'leave group')
        ->setRequirement('_group_member', 'TRUE')
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    $routes = parent::getRoutes();

    if ($route = $this->getJoinFormRoute()) {
      $routes[$this->getRouteName('join-form')] = $route;
    }

    if ($route = $this->getLeaveFormRoute()) {
      $routes[$this->getRouteName('leave-form')] = $route;
    }

    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalActions() {
    $actions = parent::getLocalActions();
    $actions['group_membership.add']['title'] = 'Add member';
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  protected function viewAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    $permissions = ['view group_membership content', 'administer members'];
    return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permissions, 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();

    // Allow members to edit their own membership data.
    if ($group_content->entity_id->entity->id() == $account->id()) {
      $permissions = ['edit own group_membership content', 'administer members'];
      return GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permissions, 'OR');
    }

    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'administer members');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    $settings = parent::getEntityReferenceSettings();
    $settings['handler_settings']['include_anonymous'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall() {
    $group_content_type_id = $this->getContentTypeConfigId();

    // Add the group_roles field to the newly added group content type. The
    // field storage for this is defined in the config/install folder. The
    // default handler for 'group_role' target entities in the 'group_type'
    // handler group is GroupTypeRoleSelection.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('group_content', 'group_roles'),
      'bundle' => $group_content_type_id,
      'label' => $this->t('Roles'),
      'settings' => [
        'handler' => 'group_type:group_role',
        'handler_settings' => [
          'group_type_id' => $this->getGroupTypeId(),
        ],
      ],
    ])->save();

    // Build the 'default' display ID for both the entity form and view mode.
    $default_display_id = "group_content.$group_content_type_id.default";

    // Build or retrieve the 'default' form mode.
    if (!$form_display = EntityFormDisplay::load($default_display_id)) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'group_content',
        'bundle' => $group_content_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Build or retrieve the 'default' view mode.
    if (!$view_display = EntityViewDisplay::load($default_display_id)) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => 'group_content',
        'bundle' => $group_content_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign widget settings for the 'default' form mode.
    $form_display->setComponent('group_roles', [
      'type' => 'options_buttons',
    ])->save();

    // Assign display settings for the 'default' view mode.
    $view_display->setComponent('group_roles', [
      'label' => 'above',
      'type' => 'entity_reference_label',
      'settings' => [
        'link' => 0,
      ],
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
