<?php

namespace Drupal\social_profile_fields\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigStorage;
use Drupal\profile\Entity\ProfileType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure social profile settings.
 */
class SocialProfileFieldsSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Field storage.
   *
   * @var \Drupal\field\FieldConfigStorage
   */
  protected $fieldStorage;

  /**
   * SocialProfileSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\field\FieldConfigStorage $field_storage
   *   Fieldstorage for the profile fields.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FieldConfigStorage $field_storage) {
    parent::__construct($config_factory);
    $this->fieldStorage = $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_profile_fields.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_profile_fields.settings');

    /** @var \Drupal\profile\Entity\ProfileType $profile_type */
    foreach (ProfileType::loadMultiple() as $profile_type) {
      $type = $profile_type->id();

      $form[$type] = [
        '#type' => 'details',
        '#title' => $this->t('Settings for profiletype: @profile_type', ['@profile_type' => $type]),
        '#description' => $this->t('Select the fields you want to use on this profile type'),
        '#open' => TRUE,
      ];

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      foreach ($this->getProfileFields($type) as $field) {
        // Loop through the fields.
        $id = $field['id'];

        // No setting is TRUE.
        $default_value = (is_null($config->get($id)) ? TRUE : FALSE);

        $form[$type][$id] = [
          '#type' => 'checkbox',
          '#title' => $field['label'],
          '#description' => $field['name'],
          '#default_value' => $default_value,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $config = $this->config('social_profile_fields.settings');

    /** @var \Drupal\profile\Entity\ProfileType $profile_type */
    foreach (ProfileType::loadMultiple() as $profile_type) {
      $type = $profile_type->id();

      /** @var \Drupal\field\Entity\FieldConfig $field_config */
      foreach ($this->getProfileFields($type) as $field) {
        $config->set($field['id'], $form_state->getValue($field['id']));
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);

    // Invalidate profile cache.
    Cache::invalidateTags(['profile', 'profile_list', 'profile_view']);
  }

  /**
   * Functions fetches profile fields from a profile type.
   *
   * @param string $profile_type_id
   *   The profile bundle.
   *
   * @return array
   *   An array of fields.
   */
  protected function getProfileFields($profile_type_id) {
    $fields = [];

    // Use storage to get only the profile fields of the current bundle type.
    $profile_fields = $this->fieldStorage->loadByProperties(['entity_type' => 'profile', 'bundle' => $profile_type_id]);

    // Loop through the fields and return the necessary values.
    foreach ($profile_fields as $profile_field) {
      // Rewrite the ID a bit, since otherwise config thinks it's an array.
      $id = str_replace('.', '_', $profile_field->id());
      // Build the array.
      $fields[$id] = [
        'id' => $id,
        'name' => $profile_field->getName(),
        'label' => $profile_field->getLabel(),
      ];
    }
    // Return the array of fields..
    return $fields;
  }

}
