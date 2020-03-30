<?php

namespace Drupal\social_activity_filter\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FilterSettingsForm.
 *
 * @package Drupal\unpd_cop\Form
 */
class FilterSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

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
    return 'social_activity_filter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_activity_filter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_activity_filter.settings');

    $form['social_activity_filter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter options list'),
    ];

    $vocabulariesList = [];

    /** @var \Drupal\taxonomy\Entity\Vocabulary */
    foreach (Vocabulary::loadMultiple() as $vid => $vocabulary) {
      $vocabulariesList[$vid] = $vocabulary->get('name');
    }

    $form['social_activity_filter']['vocabulary'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Taxonomy vocabularies'),
      '#options' => $vocabulariesList,
      '#default_value' => $config->get('vocabulary'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the configuration file.
    $config = $this->config('social_activity_filter.settings');

    $config_name = 'vocabulary';
    $config->set($config_name, $form_state->getValue($config_name))->save();

    parent::submitForm($form, $form_state);
  }

}
