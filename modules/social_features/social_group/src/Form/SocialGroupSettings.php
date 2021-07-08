<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Url;
use Drupal\crop\Entity\CropType;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
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
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentPluginManager;

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
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_content_plugin_manager
   *   The group content plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    GroupContentEnablerManagerInterface $group_content_plugin_manager
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->groupContentPluginManager = $group_content_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('plugin.manager.group_content_enabler')
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
        'allow_group_create' => $this->t('Allow regular users to create new groups'),
        'allow_group_selection_in_node' => $this->t('Allow regular users to change the group their content belong to'),
        'address_visibility_settings' => $this->t('Only show the group address to the group members'),
      ],
      '#weight' => 10,
    ];

    foreach (array_keys($form['permissions']['#options']) as $permission) {
      if ($this->hasPermission($permission)) {
        $form['permissions']['#default_value'][] = $permission;
      }
    }

    $form['default_hero'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default group hero size'),
      '#description' => $this->t('The default hero size used on this platform. Only applicable when logged-in users cannot choose a different hero size on each group.'),
      '#default_value' => $config->get('default_hero'),
      '#options' => $this->getCropTypes(),
      '#weight' => 20,
    ];

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
      '#description' => $this->t('If enabled, one node can be added as a group content to multiple groups.'),
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
      '#description' => $this->t('This option allows to control on 
        content adding/editing which group types can be used for cross-group 
        posting. It depends on "Content types" options. For example, if you 
        chose "Topic" on "Content type" option and want to have cross-group 
        posting this topic in "Flexible group" and "Open group" just select them. For 
        other group types cross-posting will be disabled.'
      ),
      '#options' => $this->getGroupTypesOptions(),
      '#default_value' => $config->get('cross_posting.group_types') ?? [],
      '#states' => [
        'visible' => [
          ':input[name="cross_posting[status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add an option for site manager to enable/disable option to choose group
    // type on page to add flexible groups.
    if (\Drupal::moduleHandler()->moduleExists('social_group_flexible_group')) {
      $form['social_group_type_required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Require group types'),
        '#description' => $this->t('When checked, a new option will appear on 
          the flexible group form which requires group creators to select a 
          group type, this allows for a better categorisation of groups in your 
          community. You can add or edit the available group types @link', [
            '@link' => Link::fromTextAndUrl('here.', Url::fromUserInput('/admin/structure/taxonomy/manage/group_type/overview'))->toString(),
          ]),
        '#default_value' => $config->get('social_group_type_required'),
      ];
    }

    return parent::buildForm($form, $form_state);
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
      ? Checkboxes::getCheckedCheckboxes($form_state->getValue(['cross_posting', 'content_types']))
      : []
    );
    $config->set('cross_posting.group_types', $cross_posting_status
      ? Checkboxes::getCheckedCheckboxes($form_state->getValue(['cross_posting', 'group_types']))
      : []
    );
    $config->set('default_hero', $form_state->getValue('default_hero'));
    $config->set('social_group_type_required', $form_state->getValue('social_group_type_required'));
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
    // @todo: maybe is better to create a list of entity bundles keyed by entity type.
    $content_types = ['topic', 'event'];
    // Add possibility to add entity types from other modules.
    $this->moduleHandler->alter('social_group_cross_posting', $content_types);

    $group_content_types = $this->groupContentPluginManager->getInstalledIds();
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
