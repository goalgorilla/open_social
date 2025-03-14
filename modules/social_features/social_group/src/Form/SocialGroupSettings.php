<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\crop\Entity\CropType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form which enables site managers to configure different options.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialGroupSettings extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_content_plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    GroupRelationTypeManagerInterface $group_content_plugin_manager,
    RendererInterface $renderer
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->groupRelationTypeManager = $group_content_plugin_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('group_relation_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_group.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_group.settings');

    $form['permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group permissions'),
      '#options' => [
        'allow_group_create' => $this->t('Allow verified users to create new groups'),
        'allow_group_selection_in_node' => $this->t('Allow verified users to change the group their content belong to'),
        'address_visibility_settings' => $this->t('Only show the group address to the group members'),
      ],
      '#weight' => 10,
      '#default_value' => [],
    ];

    foreach (array_keys($form['permissions']['#options']) as $permission) {
      if ($this->hasPermission($permission)) {
        $form['permissions']['#default_value'][] = $permission;
      }
    }

    // Cross-posting settings.
    $form['cross_posting'] = [
      '#type' => 'details',
      '#title' => $this->t('Cross-posting settings'),
      '#open' => FALSE,
      '#weight' => 30,
      '#tree' => TRUE,
    ];

    $form['cross_posting']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cross-posting'),
      '#description' => $this->t('If enabled, one content, for example, an event, can be added posted to multiple groups.'),
      '#default_value' => $config->get('cross_posting.status'),
    ];

    $form['cross_posting']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Enable cross-group posting for node types'),
      '#options' => $this->getCrossPostingEntityTypesOptions(),
      '#default_value' => $config->get('cross_posting.content_types') ?? [],
      '#states' => [
        'visible' => [
          ':input[name="cross_posting[status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // The group types list allowed to use in cross-posting.
    $form['cross_posting']['group_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group types'),
      '#description' => $this->t('Select the group types in which the content
        can be posted. It also depends on "Content types" options selected
        above. For example, if you chose "Topic" on "Content type" option and
        you want them to be posted in "Flexible group" and "Open group", you
        will have to select them here. Users will not be able to see the groups
        of other group types in options while adding/editing content.'
      ),
      '#options' => $this->getGroupTypesOptions(),
      '#default_value' => $config->get('cross_posting.group_types') ?? [],
      '#states' => [
        'visible' => [
          ':input[name="cross_posting[status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $roles = $this->currentUser()->getRoles();

    if (in_array('sitemanager', $roles) || in_array('administrator', $roles)) {
      $form['group_type'] = $this->getGroupTypeCheckboxes();
      $form['#attached']['library'][] = 'social_group/social_group_default_states';
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $content_types = Checkboxes::getCheckedCheckboxes($form_state->getValue([
      'cross_posting', 'content_types',
    ]));

    $group_types = Checkboxes::getCheckedCheckboxes($form_state->getValue([
      'cross_posting', 'group_types',
    ]));
    if ($form_state->getValue(['cross_posting', 'status'])
      && (empty($content_types) || empty($group_types))) {
      $form_state->setError($form['cross_posting'], $this->t('Please select both content type and group type which is required for enabling cross-posting feature.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('social_group.settings');

    foreach ($form_state->getValue('permissions') as $key => $value) {
      $config->set($key, !empty($value));
    }

    $cross_posting_status = $form_state->getValue(['cross_posting', 'status']);
    $config->set('cross_posting.status', $cross_posting_status);
    $config->set('cross_posting.content_types', $cross_posting_status
      ? Checkboxes::getCheckedCheckboxes($form_state->getValue([
        'cross_posting', 'content_types',
      ]))
      : []
    );
    $config->set('cross_posting.group_types', $cross_posting_status
      ? Checkboxes::getCheckedCheckboxes($form_state->getValue([
        'cross_posting', 'group_types',
      ]))
      : []
    );

    $config->save();

    $groupTypes = $form_state->getValue('group_type');
    $groupTypesSelected = [];
    $groupTypesRevoked = [];
    $role = Role::load('verified');

    // Grab all the group types the VU can create and can't create.
    foreach ($groupTypes as $key => $value) {
      $permission = 'create ' . $key . ' group';
      if ($value !== 0) {
        $groupTypesSelected[] = $permission;
        // Settings update for hook_update setting permissions that first get
        // revoked. Set it to FALSE because we allow it.
        $config->set('disallow_lu_create_groups_' . $key, FALSE)->save();
        continue;
      }

      // Settings update for hook_update setting permissions that first get
      // revoked. Set it to the key so, we know that we need to disable it.
      $config->set('disallow_lu_create_groups_' . $key, $key)->save();

      $groupTypesRevoked[] = $permission;
    }

    // For each Group Type Selected make sure LU has the permission to
    // create these groups.
    if (!empty($groupTypesSelected) && $role) {
      user_role_grant_permissions($role->id(), $groupTypesSelected);
    }
    // For each Group Type Revoked make sure LU do not get the permission to
    // create these groups.
    if (!empty($groupTypesRevoked) && $role) {
      user_role_revoke_permissions($role->id(), $groupTypesRevoked);
    }

    if (!empty($groupTypesSelected)) {
      $config->set('allow_group_create', TRUE)->save();
    }

    Cache::invalidateTags(['group_view']);
  }

  /**
   * Function to get all the Group type permission for VU.
   *
   * @return array
   *   Containing permissions for VU or empty if none.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupTypePermissionsFoVu(): array {
    $group_types = $this->entityTypeManager->getStorage('group_type')
      ->getQuery()
      ->accessCheck()
      ->execute();

    $default_types = [];

    if (is_array($group_types)) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = $this->entityTypeManager->getStorage('user_role')
        ->load('verified');

      foreach ($group_types as $group_type) {
        if ($role->hasPermission('create ' . $group_type . ' group')) {
          $default_types[$group_type] = $group_type;
        }
      }
    }

    return $default_types;
  }

  /**
   * Get all the group types for a Site Manager on the current platform.
   *
   * @return array
   *   Returns an array containing the group type elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupTypeCheckboxes(): array {
    $group_types_options = $group_types_descriptions = [];
    $storage = $this->entityTypeManager->getStorage('group_type');
    $group_types = $storage->getQuery()->accessCheck()->execute();
    $group_type_definitions = $this->entityTypeManager->getDefinition('group_type');

    $element = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group type permissions'),
      '#cache' => [
        'tags' => $group_type_definitions
          ? $group_type_definitions->getListCacheTags()
          : [],
      ],
      '#states' => [
        'disabled' => [
          ':input[name="permissions[allow_group_create]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#weight' => 15,
    ];

    if (is_array($group_types)) {
      foreach ($group_types as $group_type) {
        // Check if current user (SM) has access to create group types to
        // alter the settings for the group type.
        $access = $this->entityTypeManager
          ->getAccessControlHandler('group')
          ->createAccess($group_type, NULL, [], TRUE);

        // If we have access, make sure we render the checkboxes.
        if ($access instanceof AccessResultInterface
          && $access->isAllowed()
          && ($group_entity_type = $storage->load($group_type))
        ) {
          $title = $this->t('Allow verified users to create <b>@label</b>', [
            '@label' => $group_entity_type->label() . 's',
          ]);

          $group_types_options[$group_type] = $title;
        }

        $this->renderer->addCacheableDependency($element, $access);
      }

      arsort($group_types_options);

      $element['#options'] = $group_types_options;
    }

    // Check if authenticated user can actually create the group already
    // to pre-fill the default value.
    if (!empty($defaults = $this->getGroupTypePermissionsFoVu())) {
      $element['#default_value'] = $defaults;
    }

    return $element + $group_types_descriptions;
  }

  /**
   * Function that gets the available crop types.
   *
   * @return array
   *   The croptypes.
   */
  protected function getCropTypes() {
    $croptypes = [
      'hero',
      'hero_small',
    ];

    $options = [];

    foreach ($croptypes as $croptype) {
      $type = CropType::load($croptype);
      if ($type instanceof CropType) {
        $options[$type->id()] = $type->label();
      }
    }

    return $options;
  }

  /**
   * Check if permission is granted.
   *
   * @param string $name
   *   The permission name.
   *
   * @return bool
   *   TRUE if permission is granted.
   */
  protected function hasPermission($name) {
    if ($name === 'allow_group_create') {
      return (bool) $this->getGroupTypePermissionsFoVu();
    }

    return !empty($this->config('social_group.settings')->get($name));
  }

  /**
   * Returns node types list used as a group content.
   *
   * @return array
   *   An array with options.
   */
  private function getCrossPostingEntityTypesOptions() {
    // The list of node types allowed for cross-posting in groups.
    // @todo maybe is better to create a list of entity bundles keyed by entity type.
    $content_types = ['topic', 'event'];
    // Add possibility to add entity types from other modules.
    $this->moduleHandler->alter('social_group_cross_posting', $content_types);

    $group_content_types = $this->groupRelationTypeManager->getAllInstalledIds();
    foreach ($content_types as $bundle) {
      $plugin_id = 'group_node:' . $bundle;
      if (in_array($plugin_id, $group_content_types)) {
        $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
        $options[$bundle] = $node_type->label();
      }
    }

    return $options ?? [];
  }

  /**
   * Returns group types list options.
   *
   * @return array
   *   An array with options.
   */
  private function getGroupTypesOptions() {
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    foreach ($group_types as $id => $group_type) {
      $options[$id] = $group_type->label();
    }

    return $options ?? [];
  }

}
