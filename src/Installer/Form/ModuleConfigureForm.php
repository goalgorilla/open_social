<?php

namespace Drupal\social\Installer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social\Installer\OptionalModuleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends ConfigFormBase {

  /**
   * The module extension list.
   *
   * @var \Drupal\social\Installer\OptionalModuleManager
   */
  protected $optionalModuleManager;

  /**
   * Constructs a ModuleConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\social\Installer\OptionalModuleManager $optional_module_manager
   *   The module extension list.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OptionalModuleManager $optional_module_manager) {
    parent::__construct($config_factory);
    $this->optionalModuleManager = $optional_module_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      // Create the OptionalModuleManager ourselves because it can not be
      // available as a service yet.
      OptionalModuleManager::create($container)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_module_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Install optional modules');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('All the required modules and configuration will be automatically installed and imported. You can optionally select additional features or generated demo content.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
    ];

    // Allow automated installs to easily select all optional modules.
    $form['install_modules']['select_all'] = [
      '#type' => 'checkbox',
      '#label' => 'Install all features',
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $optional_features = $this->optionalModuleManager->getOptionalModules();
    $feature_options = array_map(
      static function ($info) {
        return $info['name'];
      },
      $optional_features
    );
    $default_features = array_keys(
      array_filter(
        $optional_features,
        static function ($info) {
          return $info['default'];
        }
      )
    );

    // Checkboxes to enable Optional modules.
    $form['install_modules']['optional_modules'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable additional features'),
      '#options' => $feature_options,
      '#default_value' => $default_features,
    ];

    $form['install_demo'] = [
      '#type' => 'container',
    ];

    $form['install_demo']['demo_content'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate demo content and users'),
      '#description' => t('Will generate files, users, groups, events, topics, comments and posts.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('select_all')) {
      // Create a simple array with all the possible optional modules.
      $optional_modules = array_keys($this->optionalModuleManager->getOptionalModules());
    }
    else {
      // Filter out the unselected modules.
      $selected_modules = array_filter($form_state->getValue('optional_modules'));
      // Create a simple array of just the module names as values.
      $optional_modules = array_values($selected_modules);
    }

    // Set the modules to be installed by Drupal in the install_profile_modules
    // step.
    $install_modules = array_merge(
      \Drupal::state()->get('install_profile_modules'),
      $optional_modules
    );
    \Drupal::state()->set('install_profile_modules', $install_modules);

    // Store whether we need to set up demo content.
    \Drupal::state()->set('social_install_demo_content', $form_state->getValue('demo_content'));
  }

}
