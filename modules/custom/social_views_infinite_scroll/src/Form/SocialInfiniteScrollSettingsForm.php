<?php

namespace Drupal\social_views_infinite_scroll\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_views_infinite_scroll\SocialInfiniteScrollManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialInfiniteScrollSettingsForm.
 *
 * @package Drupal\social_views_infinite_scroll\Form
 */
class SocialInfiniteScrollSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  const CONFIG_NAME = 'social_views_infinite_scroll.settings';

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The SocialInfiniteScrollManager manager.
   *
   * @var \Drupal\social_views_infinite_scroll\SocialInfiniteScrollManager
   */
  protected $socialInfiniteScrollManager;

  /**
   * SocialInfiniteScrollSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\social_views_infinite_scroll\SocialInfiniteScrollManager $social_infinite_scroll_manager
   *   The SocialInfiniteScrollManager manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, SocialInfiniteScrollManager $social_infinite_scroll_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
    $this->socialInfiniteScrollManager = $social_infinite_scroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @noinspection  PhpParamsInspection */
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('social_views_infinite_scroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_views_infinite_scroll_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $all_views = $this->configFactory->listAll('views');
    $blocked_views = $this->socialInfiniteScrollManager->getBlockedViews();
    $views = array_diff($all_views, $blocked_views);
    // Get the configuration file.
    $config = $this->config(self::CONFIG_NAME);

    $form['page_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Here\'s a list of views that have a "page" display.'),
    ];

    $form['settings']['views'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Views'),
    ];

    foreach ($views as $view) {
      $label = $this->configFactory->getEditable($view)->getOriginal('label');
      $changed_view_id = str_replace('.', '__', $view);

      if ($label) {
        $value = $config->get($changed_view_id);
        $default_value = !empty($value) ? $config->get($changed_view_id) : FALSE;

        $form['settings']['views'][$changed_view_id] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => $default_value,
          '#required' => FALSE,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the configuration file.
    $config = $this->config(self::CONFIG_NAME);
    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      if (strpos($key, 'views') !== FALSE) {
        $config->set($key, $value);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
