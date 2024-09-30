<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalTaskManager;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\crop\Entity\CropType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\social_group\EventSubscriber\RedirectSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form which enables site managers to configure different options.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialGroupSettings extends ConfigFormBase {

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
   * The local task manager.
   *
   * @var \Drupal\Core\Menu\LocalTaskManager
   */
  protected LocalTaskManager $localTaskManager;

  /**
   * The entity type bundles info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

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
   * @param \Drupal\Core\Menu\LocalTaskManager $local_task_manager
   *   The local task manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    GroupRelationTypeManagerInterface $group_content_plugin_manager,
    LocalTaskManager $local_task_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->groupRelationTypeManager = $group_content_plugin_manager;
    $this->localTaskManager = $local_task_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $container->get('plugin.manager.menu.local_task'),
      $container->get('entity_type.bundle.info'),
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

    // Redirects settings.
    $form['redirection'] = [
      '#type' => 'details',
      '#title' => $this->t('Redirect settings'),
      '#open' => FALSE,
      '#weight' => 40,
      '#tree' => TRUE,
    ];

    $form['redirection']['redirection_route'] = [
      '#type' => 'select',
      '#title' => $this->t('Select fallback route when current is inaccessible (Access Denied Exception)'),
      '#default_value' => $config->get('redirection.redirection_route') ?? $this->getDefaultRoute(),
      '#options' => $this->getGroupTabs(),
    ];

    $form['redirection']['group_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Applicable for Groups'),
      '#default_value' => $config->get('redirection.group_bundles') ?? [],
      '#options' => $this->getGroupBundles(),
    ];

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

    $redirection_tab = $form_state->getValue(['redirection', 'redirection_route']);
    $config->set('redirection.redirection_route', $redirection_tab);

    $group_bundles = $form_state->getValue(['redirection', 'group_bundles']);
    $config->set('redirection.group_bundles', $group_bundles
      ? Checkboxes::getCheckedCheckboxes($group_bundles) : []
    );

    $config->save();

    Cache::invalidateTags(['group_view']);
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

  /**
   * Fetch all available group tabs.
   *
   * @return array
   *   The group tabs.
   */
  protected function getGroupTabs(): array {
    $tabs = [];

    $group_tabs = $this->localTaskManager->getLocalTasksForRoute('entity.group.canonical');
    $group_tabs = $group_tabs[0];

    // Loop over the available tabs on a group.
    foreach ($group_tabs as $tab) {
      // Add to the array.
      $tabs[$tab->getRouteName()] = $tab->getTitle();
    }

    return $tabs;
  }

  /**
   * Get default redirection route.
   *
   * @return string|null
   *   The route of default route.
   */
  protected function getDefaultRoute(): ?string {
    $tabs = $this->getGroupTabs();
    return isset($tabs[RedirectSubscriber::DEFAULT_REDIRECTION_ROUTE]) ?
      RedirectSubscriber::DEFAULT_REDIRECTION_ROUTE : NULL;
  }

  /**
   * Get group bundles.
   *
   * @return array
   *   The array of group bundles.
   */
  protected function getGroupBundles(): array {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('group');
    $options = [];
    foreach ($bundles as $key => $data) {
      $options[$key] = $data['label'];
    }
    return $options;
  }

}
