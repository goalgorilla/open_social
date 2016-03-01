<?php

/**
 * @file
 * Contains \Drupal\address\Plugin\ZoneMember\ZoneMemberCountry.
 */

namespace Drupal\address\Plugin\ZoneMember;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use CommerceGuys\Zone\PostalCodeHelper;
use Drupal\address\Entity\AddressFormatInterface;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches a country, its subdivisions, and its postal codes.
 *
 * @ZoneMember(
 *   id = "country",
 *   name = @Translation("Country"),
 * )
 */
class ZoneMemberCountry extends ZoneMemberBase implements ContainerFactoryPluginInterface {

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * Constructs a new ZoneMemberCountry object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $address_format_repository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AddressFormatRepositoryInterface $address_format_repository, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'country_code' => '',
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'included_postal_codes' => '',
      'excluded_postal_codes' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $values = $form_state->getUserInput();
    if ($values) {
      $values += $this->defaultConfiguration();
    }
    else {
      $values = $this->configuration;
    }

    $wrapper_id = Html::getUniqueId('zone-members-ajax-wrapper');
    $form += [
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#after_build' => [
        [get_class($this), 'clearValues'],
      ],
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ];
    $form['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->countryRepository->getList(),
      '#default_value' => $values['country_code'],
      '#limit_validation_errors' => [],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];
    if (!empty($values['country_code'])) {
      $address_format = $this->addressFormatRepository->get($values['country_code']);
      $form = $this->buildSubdivisionElements($form, $values, $address_format);
      $form = $this->buildPostalCodeElements($form, $values, $address_format);
    }

    return $form;
  }

  /**
   * Builds the subdivision form elements.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The form values.
   * @param \Drupal\address\Entity\AddressFormatInterface $address_format
   *  The address format for the selected country.
   *
   * @return array
   *   The form with the added subdivision elements.
   */
  protected function buildSubdivisionElements(array $form, array $values, AddressFormatInterface $address_format) {
    $depth = $this->subdivisionRepository->getDepth($values['country_code']);
    if ($depth === 0) {
      // No predefined data found.
      return $form;
    }

    $labels = LabelHelper::getFieldLabels($address_format);
    $subdivision_fields = $address_format->getUsedSubdivisionFields();
    $current_depth = 1;
    foreach ($subdivision_fields as $index => $field) {
      $property = FieldHelper::getPropertyName($field);
      $parent_property = $index ? FieldHelper::getPropertyName($subdivision_fields[$index - 1]) : NULL;
      if ($parent_property && empty($values[$parent_property])) {
        // No parent value selected.
        break;
      }
      $parent_id = $parent_property ? $values[$parent_property] : NULL;
      $subdivisions = $this->subdivisionRepository->getList($values['country_code'], $parent_id);
      if (empty($subdivisions)) {
        break;
      }

      $form[$property] = [
        '#type' => 'select',
        '#title' => $labels[$field],
        '#options' => $subdivisions,
        '#default_value' => $values[$property],
        '#empty_option' => $this->t('- All -'),
      ];
      if ($current_depth < $depth) {
        $form[$property]['#ajax'] = [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
        ];
      }

      $current_depth++;
    }

    return $form;
  }

  /**
   * Builds the postal code form elements.
   *
   * @param array $form
   *   The form.
   * @param array $values
   *   The form values.
   * @param \Drupal\address\Entity\AddressFormatInterface $address_format
   *  The address format for the selected country.
   *
   * @return array
   *   The form with the added postal code elements.
   */
  protected function buildPostalCodeElements(array $form, array $values, AddressFormatInterface $address_format) {
    if (!in_array(AddressField::POSTAL_CODE, $address_format->getUsedFields())) {
      // The address format doesn't use a postal code field.
      return $form;
    }

    $form['included_postal_codes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Included postal codes'),
      '#description' => $this->t('A regular expression ("/(35|38)[0-9]{3}/") or comma-separated list, including ranges ("98, 100:200")'),
      '#default_value' => $values['included_postal_codes'],
    ];
    $form['excluded_postal_codes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Excluded postal codes'),
      '#description' => $this->t('A regular expression ("/(35|38)[0-9]{3}/") or comma-separated list, including ranges ("98, 100:200")'),
      '#default_value' => $values['excluded_postal_codes'],
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Clears the country-specific form values when the country changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $triggering_element_name = end($triggering_element['#parents']);
    if ($triggering_element_name == 'country_code') {
      $keys = ['dependent_locality', 'locality', 'administrative_area'];
      $input = &$form_state->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $this->configuration['country_code'] = $form_state->getValue('country_code');
      $this->configuration['administrative_area'] = $form_state->getValue('administrative_area');
      $this->configuration['locality'] = $form_state->getValue('locality');
      $this->configuration['dependent_locality'] = $form_state->getValue('dependent_locality');
      $this->configuration['included_postal_codes'] = $form_state->getValue('included_postal_codes');
      $this->configuration['excluded_postal_codes'] = $form_state->getValue('excluded_postal_codes');
    }
  }

  /**
  * {@inheritdoc}
  */
  public function match(AddressInterface $address) {
    if ($address->getCountryCode() != $this->configuration['country_code']) {
      return FALSE;
    }

    $administrative_area = $this->configuration['administrative_area'];
    $locality = $this->configuration['locality'];
    $dependent_locality = $this->configuration['dependent_locality'];
    if ($administrative_area && $administrative_area != $address->getAdministrativeArea()) {
      return FALSE;
    }
    if ($locality && $locality != $address->getLocality()) {
      return FALSE;
    }
    if ($dependent_locality && $dependent_locality != $address->getDependentLocality()) {
      return FALSE;
    }

    $included_postal_codes = $this->configuration['included_postal_codes'];
    $excluded_postal_codes = $this->configuration['excluded_postal_codes'];
    if (!PostalCodeHelper::match($address->getPostalCode(), $included_postal_codes, $excluded_postal_codes)) {
      return FALSE;
    }

    return TRUE;
  }

}
