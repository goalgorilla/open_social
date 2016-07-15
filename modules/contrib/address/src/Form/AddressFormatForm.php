<?php

namespace Drupal\address\Form;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Intl\Country\CountryRepositoryInterface;
use Drupal\address\LabelHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddressFormatForm extends EntityForm {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Intl\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Creates an AddressFormatForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \CommerceGuys\Intl\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CountryRepositoryInterface $country_repository) {
    $this->storage = $entity_type_manager->getStorage('address_format');
    $this->countryRepository = $country_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('address.country_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\address\Entity\AddressFormatInterface $address_format **/
    $address_format = $this->entity;
    $country_code = $address_format->getCountryCode();
    if ($country_code == 'ZZ') {
      $form['countryCode'] = [
        '#type' => 'item',
        '#title' => $this->t('Country'),
        '#markup' => $this->t('Generic'),
      ];
    }
    else {
      $form['countryCode'] = [
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#default_value' => $country_code,
        '#required' => TRUE,
        '#options' => $this->countryRepository->getList(),
        '#disabled' => !$address_format->isNew(),
      ];
    }

    $form['format'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Available tokens: @tokens', ['@tokens' => implode(', ', AddressField::getTokens())]),
      '#default_value' => $address_format->getFormat(),
      '#required' => TRUE,
    ];
    $form['requiredFields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Required fields'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#default_value' => $address_format->getRequiredFields(),
    ];
    $form['uppercaseFields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Uppercase fields'),
      '#description' => t('Uppercased on envelopes to facilitate automatic post handling.'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#default_value' => $address_format->getUppercaseFields(),
    ];
    $form['postalCodePattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code pattern'),
      '#description' => $this->t('Regular expression used to validate postal codes.'),
      '#default_value' => $address_format->getPostalCodePattern(),
    ];
    $form['postalCodePrefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code prefix'),
      '#description' => $this->t('Added to postal codes when formatting an address for international mailing.'),
      '#default_value' => $address_format->getPostalCodePrefix(),
      '#size' => 5,
    ];

    $form['postalCodeType'] = [
      '#type' => 'select',
      '#title' => $this->t('Postal code type'),
      '#default_value' => $address_format->getPostalCodeType(),
      '#options' =>  LabelHelper::getPostalCodeLabels(),
      '#empty_value' => '',
    ];
    $form['dependentLocalityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Dependent locality type'),
      '#default_value' => $address_format->getDependentLocalityType(),
      '#options' => LabelHelper::getDependentLocalityLabels(),
      '#empty_value' => '',
    ];
    $form['localityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Locality type'),
      '#default_value' => $address_format->getLocalityType(),
      '#options' => LabelHelper::getLocalityLabels(),
      '#empty_value' => '',
    ];
    $form['administrativeAreaType'] = [
      '#type' => 'select',
      '#title' => $this->t('Administrative area type'),
      '#default_value' => $address_format->getAdministrativeAreaType(),
      '#options' => LabelHelper::getAdministrativeAreaLabels(),
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Disallow adding an address format for a country that already has one.
    if ($this->entity->isNew()) {
      $country = $form_state->getValue('countryCode');
      if ($this->storage->load($country)) {
        $form_state->setErrorByName('countryCode', $this->t('The selected country already has an address format.'));
      }
    }

    // Require the matching type field for the fields specified in the format.
    $format = $form_state->getValue('format');
    $requirements = [
      '%postalCode' => 'postalCodeType',
      '%dependentLocality' => 'dependentLocalityType',
      '%locality' => 'localityType',
      '%administrativeArea' => 'administrativeAreaType',
    ];
    foreach ($requirements as $token => $required_field) {
      if (strpos($format, $token) !== FALSE && !$form_state->getValue($required_field)) {
        $title = $form[$required_field]['#title'];
        $form_state->setErrorByName($required_field, $this->t('%title is required.', ['%title' => $title]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label address format.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
