<?php

namespace Drupal\social_user_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialUserExportSettingsForm.
 *
 * @package Drupal\social_user_export\Form
 */
class SocialUserExportSettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for export plugins.
   *
   * @var \Drupal\social_user_export\Plugin\UserExportPluginManager
   */
  protected $exportPluginManager;

  /**
   * Constructs a new DataPolicyRevisionDeleteForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\social_user_export\Plugin\UserExportPluginManager $export_plugin_manager
   *   The plugin manager for export plugins.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserExportPluginManager $export_plugin_manager) {
    parent::__construct($config_factory);

    $this->exportPluginManager = $export_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.user_export_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_user_export.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_export_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_user_export.settings');

    $options = [];

    $export_plugins = $this->exportPluginManager->getDefinitions();
    foreach ($export_plugins as $plugin) {
      $options[$plugin['id']] = $plugin['label'];
    }

    // Show a list of plugins that can be enabled or disabled for user
    // exporting.
    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Data available for users to export'),
      '#description' => $this->t('Check any data that users are allowed to export in for example the events and groups they manage.'),
      '#options' => $options,
      '#default_value' => $config->get('plugins'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('social_user_export.settings')
      ->set('plugins', $form_state->getValue('plugins'))
      ->save();
  }

}
