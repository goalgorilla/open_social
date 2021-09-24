<?php

namespace Drupal\social_scroll\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_scroll\SocialScrollManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for providing a configuration form.
 *
 * @package Drupal\social_scroll\Form
 */
class SocialScrollSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  const CONFIG_NAME = 'social_scroll.settings';

  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The social scroll manager.
   *
   * @var \Drupal\social_scroll\SocialScrollManagerInterface
   */
  protected $socialScrollManager;

  /**
   * SocialScrollSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\social_scroll\SocialScrollManagerInterface $social_scroll_manager
   *   The SocialScrollManager manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, SocialScrollManagerInterface $social_scroll_manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
    $this->socialScrollManager = $social_scroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('social_scroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_scroll_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return mixed[]
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $all_views = array_map(function ($view_id) {
      return str_replace('views.view.', '', $view_id);
    }, $this->configFactory->listAll('views'));

    $blocked_views = $this->socialScrollManager->getBlockedViewIds();
    $views = array_diff($all_views, $blocked_views);
    $config = $this->config(self::CONFIG_NAME);

    $form['page_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Here\'s a list of views that have a "page" display.'),
    ];

    $form['settings']['button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config->get('button_text'),
      '#maxlength' => '255',
    ];

    $form['settings']['automatically_load_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically Load Content'),
      '#description' => $this->t('Automatically load subsequent pages as the user scrolls.'),
      '#default_value' => $config->get('automatically_load_content'),
    ];

    $form['settings']['views'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Views'),
    ];

    $options = [];
    foreach ($views as $view) {
      $current_view = $this->configFactory->getEditable($this->socialScrollManager->getConfigName($view));
      $label = $current_view->getOriginal('label');

      if ($label) {
        $displays = $current_view->getOriginal('display');
        unset($displays['default']);
        $pages = [];

        foreach ($displays as $id => $display) {
          if ($display['display_plugin'] !== 'block') {
            $pages[] = $id;
          }
        }

        if (!empty($pages)) {
          $options[(string) $view] = $label;
        }
      }
    }

    $form['settings']['views']['list'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => array_keys($this->socialScrollManager->getEnabledViewIds()),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::CONFIG_NAME)
      ->set('views_list', $form_state->getValue('list'))
      ->set('button_text', $form_state->getValue('button_text'))
      ->set('automatically_load_content', $form_state->getValue('automatically_load_content'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
