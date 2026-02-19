<?php

declare(strict_types=1);

namespace Drupal\social_group_request\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_group_request\Hooks\SocialGroupRequestFormHooks;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Group request (request to join) customization.
 *
 * Allows site managers to enable per-group-type customization of the
 * request-to-join form (required message, description, default text).
 */
class GroupRequestSettingsForm extends ConfigFormBase {

  /**
   * The form hooks service (for group types and config helpers).
   *
   * @var \Drupal\social_group_request\Hooks\SocialGroupRequestFormHooks
   */
  protected SocialGroupRequestFormHooks $formHooks;

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\social_group_request\Hooks\SocialGroupRequestFormHooks $form_hooks
   *   The social group request form hooks service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SocialGroupRequestFormHooks $form_hooks,
  ) {
    parent::__construct($config_factory);
    $this->formHooks = $form_hooks;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('social_group_request.form_hooks'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_group_request.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_group_request_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $group_types = $this->formHooks->getGroupTypesWithMembershipRequest();
    $allow_customize = $this->formHooks->getAllowCustomizeByGroupType();

    if ($group_types === []) {
      $form['empty'] = [
        '#markup' => $this->t('No group types use the request-to-join method. Enable it on a group type to configure customization here.'),
      ];
      return parent::buildForm($form, $form_state);
    }

    $form['allow_customize'] = [
      '#type' => 'details',
      '#title' => $this->t('Allow managers to customize the request to join form'),
      '#description' => $this->t('When enabled, managers of the group type can make the message required, personalize the form description, and provide default message text.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach ($group_types as $group_type_id => $group_type) {
      $form['allow_customize'][$group_type_id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow <strong>@label</strong> managers to customize the request to join form', [
          '@label' => $group_type->label(),
        ]),
        '#default_value' => !empty($allow_customize[$group_type_id]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $group_types = $this->formHooks->getGroupTypesWithMembershipRequest();
    if ($group_types !== []) {
      $submitted = $form_state->getValue('allow_customize', []);
      $allow_customize = [];
      foreach (array_keys($group_types) as $group_type_id) {
        $allow_customize[$group_type_id] = !empty($submitted[$group_type_id]);
      }
      $config = $this->configFactory->getEditable('social_group_request.settings');
      $config->set('allow_customize', $allow_customize);
      $config->clear('allow_group_managers_customize');
      $config->clear('allow_organization_managers_customize');
      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

}
