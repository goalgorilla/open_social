<?php

namespace Drupal\social_pwa\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Social PWA Manifest.
 */
class ManifestSettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_pwa_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_pwa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the default settings for the Social PWA Module.
    $config = $this->config('social_pwa.settings');
    // Get the specific icons. Needed to get the correct path of the file.
    $icon = \Drupal::config('social_pwa.settings')->get('icons.icon');

    // Start form.
    $form['social_pwa_manifest_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Push notification configuration'),
      '#open' => FALSE,
    ];
    $form['social_pwa_manifest_settings']['description'] = [
      '#markup' => $this->t('In order for push notifications and your Progressive Web App to work you will need to configure the settings below.'),
    ];

    // For now we will turn everything on or off with one checkbox. However we
    // should make it possible to easily extend this in the future.
    $form['social_pwa_manifest_settings']['status_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable push notifications and PWA'),
      '#default_value' => NULL !== $config->get('status.all') ? $config->get('status.all') : TRUE,
      '#description' => $this->t('Disabling the push notifications and PWA will ensure that no user is able to configure and receive push notifications. Nor will any other Progressive Web App functions be available.'),
    ];

    $form['social_pwa_manifest_settings']['short_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short name'),
      '#size' => 12,
      '#default_value' => $config->get('short_name'),
      '#required' => TRUE,
      '#description' => $this->t('This is the name the user will see when they add your website to their homescreen. You might want to keep this short.'),
    ];
    $form['social_pwa_manifest_settings']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 30,
      '#default_value' => $config->get('name'),
      '#description' => $this->t('Enter the name of your website.'),
    ];
    $form['social_pwa_manifest_settings']['icon'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('General App Icon'),
      '#description' => $this->t('Provide a square (.png) image. This image serves as your icon when the user adds the website to their home screen. <i>Minimum dimensions are 512px by 512px.</i>'),
      '#default_value' => $icon ?: [],
      '#required' => TRUE,
      '#upload_location' => \Drupal::config('system.file')->get('default_scheme') . '://images/touch/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png'],
        'file_validate_image_resolution' => ['512x512', '512x512'],
      ],
    ];
    $form['social_pwa_manifest_settings']['background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#default_value' => $config->get('background_color'),
      '#description' => $this->t('Select a background color for the launch screen. This is shown when the user opens the website from their homescreen.'),
    ];
    $form['social_pwa_manifest_settings']['theme_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Theme Color'),
      '#default_value' => $config->get('theme_color'),
      '#description' => $this->t('This color is used to create a consistent experience in the browser when the users launch your website from their homescreen.'),
    ];

    // Sub-section for Advanced Settings.
    $form['social_pwa_manifest_advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];
    $form['social_pwa_manifest_advanced_settings']['notice'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Please notice:'),
      '#open' => FALSE,
      '#description' => $this->t('These settings have been set automatically to serve the most common use cases. Only change these settings if you know what you are doing.'),
    ];
    $form['social_pwa_manifest_advanced_settings']['start_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start URL'),
      '#size' => 15,
      '#disabled' => FALSE,
      '#description' => $this->t('The scope for the Service Worker.'),
      '#default_value' => $config->get('start_url'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];
    $form['social_pwa_manifest_advanced_settings']['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#default_value' => $config->get('display'),
      '#description' => $this->t('<u>When the site is being launched from the homescreen, you can launch it in:</u></br><b>Fullscreen:</b><i> This will cover up the entire display of the device.</i></br><b>Standalone:</b> <i>(default) Kind of the same as Fullscreen, but only shows the top info bar of the device. (Telecom provider, time, battery etc.)</i></br><b>Browser:</b> <i>It will simply just run from the browser on your device with all the user interface elements of the browser.</i>'),
      '#options' => [
        'fullscreen' => $this->t('Fullscreen'),
        'standalone' => $this->t('Standalone'),
        'browser' => $this->t('Browser'),
      ],
    ];
    $form['social_pwa_manifest_advanced_settings']['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#default_value' => $config->get('orientation'),
      '#description' => $this->t('Configures if the site should run in <b>Portrait</b> (default) or <b>Landscape</b> mode on the device when being launched from the homescreen.'),
      '#options' => [
        'portrait' => $this->t('Portrait'),
        'landscape' => $this->t('Landscape'),
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate front page path.
    if (($value = $form_state->getValue('start_url')) && $value[0] !== '/') {
      $form_state->setErrorByName('start_url', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('start_url')]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $icon = $form_state->getValue('icon');
    // Load the object of the file by its fid.
    $file = File::load($icon[0]);
    // Set the status flag permanent of the file object.
    if (!empty($file)) {
      // Flag the file permanent.
      $file->setPermanent();
      // Save the file in the database.
      $file->save();
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'social_pwa', 'icon', \Drupal::currentUser()->id());
    }

    $config = $this->config('social_pwa.settings');
    $config->set('status.all', $form_state->getValue('status_all'))
      ->set('name', $form_state->getValue('name'))
      ->set('short_name', $form_state->getValue('short_name'))
      ->set('start_url', $form_state->getValue('start_url'))
      ->set('background_color', $form_state->getValue('background_color'))
      ->set('theme_color', $form_state->getValue('theme_color'))
      ->set('display', $form_state->getValue('display'))
      ->set('orientation', $form_state->getValue('orientation'))
      ->set('icons.icon', $form_state->getValue('icon'))
      ->save();
    
    Cache::invalidateTags(['manifestjson']);

    parent::submitForm($form, $form_state);
  }

}
