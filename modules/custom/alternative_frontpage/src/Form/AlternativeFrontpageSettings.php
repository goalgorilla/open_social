<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The AlternativeFrontpageSettings class.
 */
class AlternativeFrontpageSettings extends ConfigFormBase {

  public const CONFIG_NAME = 'alternative_frontpage.settings';

  public const FORM_PREFIX = 'frontpage_for_';

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'alternative_frontpage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['pages'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    foreach (user_role_names() as $role_id => $role_label) {
      // No configuration/redirection is needed for administrators.
      if ($role_id === 'administrator') {
        continue;
      }

      $form['pages'][self::FORM_PREFIX . $role_id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Frontpage for @role_label', ['@role_label' => $role_label . 's']),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $this->config(self::CONFIG_NAME)->get('pages.frontpage_for_' . $role_id),
      ];
    }

    $form['description'] = [
      '#markup' => $this->t('Enter the front page for users per role. This setting will override the homepage which is set in the Site Configuration form. Enter the path starting with a forward slash. Default: /stream.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $pages = $form_state->getValue('pages');

    foreach ($pages as $id => $url) {
      if (empty($url)) {
        continue;
      }

      $role_id = str_replace(self::FORM_PREFIX, '', $id);

      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($url)) {
        $form_state->setErrorByName($id, $this->t('The path for the @role_id frontpage is not valid.', ['@role_id' => $role_id]));
      }
      elseif (strpos($url, '/') !== 0) {
        $form_state->setErrorByName($id, $this->t('The path for the @role_id frontpage should start with a forward slash.', ['@role_id' => $role_id]));
      }
      elseif (!$this->isAllowedPath($url)) {
        $form_state->setErrorByName($id, $this->t('The path for the @role_id frontpage is not allowed.', ['@role_id' => $role_id]));
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
  private function isAllowedPath(string $path): bool {
    $unallowed_paths = [
      '/user/logout',
      '/ajax',
    ];
    foreach ($unallowed_paths as $unallowed_path) {
      if (strpos($path, $unallowed_path) === 0) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValue('pages');
    $this->config(self::CONFIG_NAME)->set('pages', $values)->save();

    $this->configFactory->getEditable('system.site')
      ->set('page.front', $form_state->getValue([
        'pages',
        self::FORM_PREFIX . RoleInterface::ANONYMOUS_ID,
      ]))
      ->save();
  }

}
