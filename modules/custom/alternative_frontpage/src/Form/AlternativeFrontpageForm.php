<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;

/**
 * Form handler for the AlternativeFrontpage add and edit forms.
 */
class AlternativeFrontpageForm extends EntityForm {

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The config factory to perform operations on the configs.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->typedConfigManager = $container->get('config.typed');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pathValidator = $container->get('path.validator');
    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\alternative_frontpage\Entity\AlternativeFrontpage $settings */
    $settings = $this->entity;

    $ignored_roles = ['administrator'];
    $options = [];

    foreach (Role::loadMultiple() as $role) {
      if (in_array($role->id(), $ignored_roles, TRUE)) {
        continue;
      }
      $options[$role->id()] = $role->label();
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $settings->label(),
      '#description' => $this->t("Label for the Frontpage Setting."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $settings->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$settings->isNew(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage path'),
      '#maxlength' => 255,
      '#default_value' => $settings->path,
      '#description' => $this->t("Enter the frontpage. This setting will override the homepage which is set in the Site Configuration form. Enter the path starting with a forward slash. Default: /stream."),
      '#required' => TRUE,
    ];

    $form['roles_target_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('User roles'),
      '#options' => $options,
      '#default_value' => $settings->roles_target_id,
      '#description' => $this->t('Which roles should it apply to'),
      '#required' => TRUE,
      '#attributes' => [
        'required' => 'required',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $path = $form_state->getValue('path');
    $role = $form_state->getValue('roles_target_id');
    $id = $form_state->getValue('id');

    if ($this->roleExist($role, $id)) {
      $form_state->setErrorByName('roles_target_id', $this->t('There is already a setting created for this role.'));
    }

    if ($path) {
      if (!$this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage is not valid.'));
      }
      elseif (strpos($path, '/') !== 0) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage should start with a forward slash.'));
      }
      elseif (!$this->isAllowedPath($path)) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage is not allowed.'));
      }
      // Check access to the provided path for anonymous users.
      elseif ($role === 'anonymous' && !$this->isPathPublicContent($path)) {
        $form_state->setErrorByName('path', $this->t('The path for the frontpage is not allowed for anonymous users.'));
      }
    }
  }

  /**
   * Checks if a content path has public visibility.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   Returns true when content path has public visibility.
   */
  private function isPathPublicContent($path) {
    /** @var \Drupal\Core\Url $url */
    $url = $this->pathValidator->getUrlIfValid($path);
    $params = $url->getRouteParameters();
    $entity_type = key($params);

    if ($entity_type === 'node') {
      /** @var \Drupal\node\Entity\Node $node */
      $node = $this->entityTypeManager->getStorage($entity_type)
        ->load($params[$entity_type]);

      if ($node->hasField('field_content_visibility') && $node->get('field_content_visibility')->getString() !== 'public') {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\alternative_frontpage\Entity\AlternativeFrontpage $settings */
    $settings = $this->entity;
    $status = $settings->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label settting.', [
        '%label' => $settings->label(),
      ]));
    }

    // In case if we change the front page for the anonymous user we wanna
    // also change the system front page path in site configuration.
    if ($settings->roles_target_id === RoleInterface::ANONYMOUS_ID) {
      $this->configFactory->getEditable('system.site')
        ->set('page.front', $settings->path)
        ->save();
    }

    $form_state->setRedirect('entity.alternative_frontpage.collection');

    return parent::save($form, $form_state);
  }

  /**
   * Check whether an Alternative Frontpage configuration entity exists.
   *
   * @param int|string $id
   *   Entity ID to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function exist($id): bool {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Check if there is already a configuration entity with the same user role.
   *
   * @param string $roles_target_id
   *   Role target to check.
   * @param int|string $id
   *   Entity ID to check.
   *
   * @return bool
   *   Returns true when the configuration exists.
   */
  public function roleExist(string $roles_target_id, $id): bool {
    $entity = $this->entityTypeManager->getStorage('alternative_frontpage')->getQuery()
      ->condition('roles_target_id', $roles_target_id)
      ->condition('id', $id, '<>')
      ->execute();
    return (bool) $entity;
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

}
