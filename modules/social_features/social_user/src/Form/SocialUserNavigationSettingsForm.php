<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialUserNavigationSettingsForm.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserNavigationSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_navigation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_user.navigation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_user.navigation.settings');

    $form['navigation_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display icon configuration'),
      '#description' => $this->t('Select which icons you want to show or hide in the main (top) navigation bar.'),
    ];

    // Check if the module is enabled before we show the setting.
    if ($this->moduleHandler->moduleExists('social_private_message')) {
      $form['navigation_settings']['display_social_private_message_icon'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display the <b>Social Private Message</b> (envelope) icon.'),
        '#default_value' => $config->get('display_social_private_message_icon'),
        '#required' => FALSE,
        '#description' => $this->t("<i>Disabling this won't disable the Social Private Message feature, but only hide the icon from the navigation.</i>"),
      ];
    }
    // Check if the module is enabled before we show the setting.
    if ($this->moduleHandler->moduleExists('social_group')) {
      $form['navigation_settings']['display_my_groups_icon'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display the <b>My Groups</b> icon in the main navigation'),
        '#default_value' => $config->get('display_my_groups_icon'),
        '#required' => FALSE,
        '#description' => $this->t("<i>Disabling this will simply hide the 'My Groups' button from the main navigation.</i>"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_user.navigation.settings');
    $config->set('display_social_private_message_icon', $form_state->getValue('display_social_private_message_icon'))
      ->set('display_my_groups_icon', $form_state->getValue('display_my_groups_icon'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
