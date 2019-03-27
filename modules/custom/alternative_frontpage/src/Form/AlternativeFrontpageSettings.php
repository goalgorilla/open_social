<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AlternativeFrontpageSettings.
 */
class AlternativeFrontpageSettings extends ConfigFormBase {

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'alternative_frontpage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alternative_frontpage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alternative_frontpage.settings');
    $site_config = $this->config('system.site');
    $form['frontpage_for_anonymous_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage for anonymous users'),
      '#description' => $this->t('Enter the frontpage for anonymous users. This setting will override the homepage which is set in the Site Configuration form. Enter the path starting with a forward slash. Default: /stream.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $site_config->get('page.front'),
    ];
    $form['frontpage_for_authenticated_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage for authenticated users'),
      '#description' => $this->t('Enter the frontpage for authenticated users. When the value is left empty it will use the anonymous homepage for authenticated users as well. Enter the path starting with a forward slash. Default: /stream.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('frontpage_for_authenticated_user'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $frontpage_for_anonymous_user = $form_state->getValue('frontpage_for_anonymous_users');
    $frontpage_for_authenticated_user = $form_state->getValue('frontpage_for_authenticated_user');

    if ($frontpage_for_anonymous_user) {
      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($frontpage_for_anonymous_user)) {
        $form_state->setErrorByName('frontpage_for_anonymous_users', $this->t('The path for the anonymous frontpage is not valid.'));
      }
      elseif (substr($frontpage_for_anonymous_user, 0, 1) !== '/') {
        $form_state->setErrorByName('frontpage_for_anonymous_users', $this->t('The path for the anonymous frontpage should start with a forward slash.'));
      }
      elseif (!$this->isAllowedPath($frontpage_for_anonymous_user)) {
        $form_state->setErrorByName('frontpage_for_anonymous_users', $this->t('The path for the anonymous frontpage is not allowed.'));
      }
    }
    else {
      $form_state->setErrorByName('frontpage_for_anonymous_users', $this->t('The path for the anonymous frontpage cannot be empty.'));
    }
    if ($frontpage_for_authenticated_user) {
      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($frontpage_for_authenticated_user)) {
        $form_state->setErrorByName('frontpage_for_authenticated_user', $this->t('The path for the authenticated frontpage is not valid.'));
      }
      elseif (substr($frontpage_for_authenticated_user, 0, 1) !== '/') {
        $form_state->setErrorByName('frontpage_for_authenticated_user', $this->t('The path for the authenticated frontpage should start with a forward slash.'));
      }
      elseif (!$this->isAllowedPath($frontpage_for_authenticated_user)) {
        $form_state->setErrorByName('frontpage_for_authenticated_user', $this->t('The path for the authenticated frontpage is not allowed.'));
      }
    }
  }

  /**
   * Checks if a path is allowed.
   *
   * Some paths are not allowed, e.g. /user/logout or /ajax.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   Returns true when path is allowed.
   */
  private function isAllowedPath($path) {
    $unallowed_paths = [
      '/user/logout',
      '/ajax',
    ];
    foreach ($unallowed_paths as $unallowed_path) {
      if ($unallowed_path === substr($path, 0, strlen($unallowed_path))) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('alternative_frontpage.settings')
      ->set('frontpage_for_authenticated_user', $form_state->getValue('frontpage_for_authenticated_user'))
      ->save();

    $this->configFactory->getEditable('system.site')->set('page.front', $form_state->getValue('frontpage_for_anonymous_users'))->save();
  }

}
