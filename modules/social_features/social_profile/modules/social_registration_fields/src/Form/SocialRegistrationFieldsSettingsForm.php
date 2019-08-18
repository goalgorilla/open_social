<?php

namespace Drupal\social_registration_fields\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialRegistrationFieldsSettingsForm.
 */
class SocialRegistrationFieldsSettingsForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'social_registration_fields.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_registration_fields_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $field_instances = array_filter($this->entityFieldManager->getFieldDefinitions('profile', 'profile'), function ($field_definition) {
      return ($field_definition instanceof FieldConfigInterface && $field_definition->get('field_type') !== 'text_long');
    });

    $storage = $form_state->getStorage();
    $storage['field_instances'] = $field_instances;
    $form_state->setStorage($storage);

    $form['social_register_fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field label'),
        $this->t('Show on sign-up form'),
        $this->t('Set as mandatory'),
      ],
    ];

    foreach ($field_instances as $field_key => $field_instance) {
      $form['social_register_fields'][$field_key]['label'] = [
        '#markup' => $field_instance->label(),
      ];

      $form['social_register_fields'][$field_key]['show_on_signup'] = [
        '#type' => 'checkbox',
        '#default_value' => $config->get($field_key)['show_on_signup'],
        '#id' => $field_key . '_show_on_signup',
      ];

      $form['social_register_fields'][$field_key]['mandatory'] = [
        '#type' => 'checkbox',
        '#default_value' => $config->get($field_key)['mandatory'],
        '#states' => [
          'disabled' => [
            '#' . $field_key . '_show_on_signup' => ['checked' => FALSE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field_instances = $form_state->getStorage()['field_instances'];

    foreach ($field_instances as $field_key => $field_instance) {
      $this->configFactory->getEditable(static::SETTINGS)
        ->set($field_key, [
          'show_on_signup' => $form_state->getValue('social_register_fields')[$field_key]['show_on_signup'],
          'mandatory' => $form_state->getValue('social_register_fields')[$field_key]['mandatory'],
        ])
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
