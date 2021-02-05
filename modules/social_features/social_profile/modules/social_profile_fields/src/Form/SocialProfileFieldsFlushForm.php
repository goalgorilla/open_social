<?php

namespace Drupal\social_profile_fields\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigStorage;
use Drupal\profile\ProfileStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SocialProfileFieldsFlushForm.
 *
 * Provides confirmation form for resetting a vocabulary to alphabetical order.
 */
class SocialProfileFieldsFlushForm extends ConfirmFormBase {


  /**
   * Profilestorage.
   *
   * @var \Drupal\profile\ProfileStorage
   */
  protected $profileStorage;

  /**
   * Configstorage.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


  /**
   * Fiekdconfigstorage.
   *
   * @var \Drupal\field\FieldConfigStorage
   */
  protected $fieldStorage;

  /**
   * Constructs a new ExportUserConfirm.
   *
   * @param \Drupal\profile\ProfileStorage $profiel_storage
   *   The profile storage.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.
   * @param \Drupal\field\FieldConfigStorage $field_storage
   *   The field storage.
   */
  public function __construct(ProfileStorage $profiel_storage, ConfigFactory $config_factory, FieldConfigStorage $field_storage) {
    $this->profileStorage = $profiel_storage;
    $this->configFactory = $config_factory;
    $this->fieldStorage = $field_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('profile'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_fields_flush_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Flush profile.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('social_profile_fields.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, continue');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pids = \Drupal::entityQuery('profile')
      ->condition('type', 'profile')
      ->execute();

    $fields = $this->getUnselectedFields();

    $batch = [
      'title' => t('Flushing profiles.'),
      'operations' => [
        [
          '\Drupal\social_profile_fields\SocialProfileFieldsBatch::performFlush',
          [$pids, $fields],
        ],
      ],
      'finished' => '\Drupal\social_profile_fields\SocialProfileFieldsBatch::performFlushFinishedCallback',
    ];
    batch_set($batch);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    $fields = $this->getUnselectedFields();
    $field_string = implode(', ', $fields);

    return $this->t('<strong>WARNING</strong>: Flushing profile data will permanently <strong>remove all data</strong> from the following fields from the database: %fields. The search indexes may also be cleared and will need re-indexing. This <strong>cannot be undone</strong>. Are you sure you want to continue?', ['%fields' => $field_string]);
  }

  /**
   * Function that return an array of field names.
   *
   * @return array
   *   An array of field names.
   */
  protected function getUnselectedFields() {
    $profile_fields = $this->fieldStorage->loadByProperties(['entity_type' => 'profile', 'bundle' => 'profile']);
    $settings = $this->configFactory->get('social_profile_fields.settings');
    $empty = [];

    /** @var \Drupal\field\Entity\FieldConfig $value */
    foreach ($profile_fields as $key => $value) {
      $setting_id = str_replace('.', '_', $key);
      $sval = $settings->get($setting_id);

      if (isset($sval) && $sval == FALSE) {
        $empty[] = $value->getName();
      }

      if ($setting_id === 'profile_profile_field_profile_address') {
        if (isset($sval) && $sval == FALSE) {
          $empty[] = 'country';
        }

        $city_val = $settings->get('profile_address_field_city');
        if (isset($city_val) && $city_val == FALSE) {
          $empty[] = 'locality';
        }

        $address_val = $settings->get('profile_address_field_address');
        if (isset($address_val) && $address_val == FALSE) {
          $empty[] = 'addressLine1';
        }

        $postalcode_val = $settings->get('profile_address_field_postalcode');
        if (isset($postalcode_val) && $postalcode_val == FALSE) {
          $empty[] = 'postalCode';
        }
      }
    }
    return $empty;
  }

}
