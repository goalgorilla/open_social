<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\CropType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialGroupSettings.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialGroupSettings extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The module handler.
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

    $form['allow_group_selection_in_node'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow logged-in users to change or remove a group when editing content'),
      '#description' => $this->t('When checked, logged-in users can also move content to or out of a group after the content is created. Users can only move content to a group the author is a member of.'),
      '#default_value' => $config->get('allow_group_selection_in_node'),
    ];

    $form['default_hero'] = [
      '#type' => 'select',
      '#title' => $this->t('The default hero image.'),
      '#description' => $this->t('The default hero size used on this platform. Only applicable when logged-in users cannot choose a different hero size on each group.'),
      '#default_value' => $config->get('default_hero'),
      '#options' => $this->getCropTypes(),
    ];

    // Check if the module is enabled before we show the setting.
    if ($this->moduleHandler->moduleExists('social_group_flexible_group')) {
      $form['visibility_settings'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Flexible group visibility settings'),
      ];
      $form['visibility_settings']['available_visibility_options'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select available visibility options'),
        '#description' => $this->t('<strong>Note:</strong> Since flexible groups can contain content with multiple visibility options, you can determine which visibility options should be available when creating a new <strong>flexible</strong> group.'),
        '#default_value' => $config->get('available_visibility_options'),
        '#options' => $this->getVisibilityOptions(),
      ];
    }

    // If public visibility is disabled, don't show it here.
    $disable_public_visibility = $this->config('entity_access_by_field.settings')->get('disable_public_visibility');
    if ($disable_public_visibility === (int) TRUE) {
      unset($form['visibility_settings']['available_visibility_options']['#options']['public']);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('social_group.settings');
    $config->set('allow_group_selection_in_node', $form_state->getValue('allow_group_selection_in_node'));
    $config->set('default_hero', $form_state->getValue('default_hero'));

    if ($this->moduleHandler->moduleExists('social_group_flexible_group')) {
      $config->set('available_visibility_options', $form_state->getValue('available_visibility_options'));
    }

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
   * Return the available group content visibility options.
   *
   * @return array
   *   Array with options.
   */
  protected function getVisibilityOptions() {
    $options = [];
    $visibility_options = social_group_get_allowed_visibility_options_per_group_type(NULL);
    foreach ($visibility_options as $key => $value) {
      $options[$key] = ucfirst($key);
    }

    return $options;
  }

}
