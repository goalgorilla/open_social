<?php

namespace Drupal\social_views_infinite_scroll\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
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
    /* @noinspection  PhpParamsInspection */
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
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
    $views = $this->configFactory->listAll('views');

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
      $current_view = $this->configFactory->getEditable($view);
      $display = $current_view->get('display');

      if ($display && count($display)) {
        foreach ($display as $index => $data) {
          if ($data['display_plugin'] === 'block') {
            unset($display[$index]);
          }
        }

        $changed_view_id = str_replace('.', '__', $view);
        $label = $current_view->getOriginal('label');

        if ($label) {
          $value = $config->get($changed_view_id);
          $default_value = !empty($value) ? $config->get($changed_view_id) : [];
          $options = array_column($display, 'display_title', 'id');

          foreach ($options as $id => $option) {
            $options[$id] = $option . ' ' . '(' . $id . ')';
            if (isset($display[$id]['display_options']['path'])) {
              $options[$id] .= '<br>' . $display[$id]['display_options']['path'];
            }
          }

          $form['settings']['views'][$changed_view_id] = [
            '#type' => 'checkboxes',
            '#title' => '<strong>' . $label . '</strong>',
            '#default_value' => $default_value,
            '#options' => !empty($options) ? $options : [],
          ];

        }
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
