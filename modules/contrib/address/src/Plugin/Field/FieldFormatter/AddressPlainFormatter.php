<?php

namespace Drupal\address\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\address\Entity\AddressFormatInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "address_plain",
 *   label = @Translation("Plain"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressPlainFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
   * Constructs an AddressPlainFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $address_format_repository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AddressFormatRepositoryInterface $address_format_repository, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    // @see \Drupal\Core\Field\FormatterPluginManager::createInstance().
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewElement($item, $langcode);
    }

    return $elements;
  }

  /**
   * Builds a renderable array for a single address item.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(AddressInterface $address, $langcode) {
    $country_code = $address->getCountryCode();
    $countries = $this->countryRepository->getList();
    $address_format = $this->addressFormatRepository->get($country_code, $address->getLocale());
    $values = $this->getValues($address, $address_format);

    $element = [
      '#theme' => 'address_plain',
      '#recipient' => $values['recipient'],
      '#organization' => $values['organization'],
      '#address_line1' => $values['addressLine1'],
      '#address_line2' => $values['addressLine2'],
      '#postal_code' => $values['postalCode'],
      '#sorting_code' => $values['sortingCode'],
      '#administrative_area' => $values['administrativeArea'],
      '#locality' => $values['locality'],
      '#dependent_locality' => $values['dependentLocality'],
      '#country' => [
        'code' => $country_code,
        'name' => $countries[$country_code],
      ],
      '#cache' => [
        'contexts' => [
          'languages:' . LanguageInterface::TYPE_INTERFACE,
        ],
      ],
    ];

    return $element;
  }

  /**
   * Gets the address values used for rendering.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   * @param \Drupal\address\Entity\AddressFormatInterface $address_format
   *   The address format.
   *
   * @return array
   *   The values, keyed by address field.
   */
  protected function getValues(AddressInterface $address, AddressFormatInterface $address_format) {
    $values = [];
    foreach (AddressField::getAll() as $field) {
      $getter = 'get' . ucfirst($field);
      $values[$field] = $address->$getter();
    }

    foreach ($address_format->getUsedSubdivisionFields() as $field) {
      $value = $values[$field];
      // The template needs access to both the subdivision code and name.
      $values[$field] = [
        'code' => '',
        'name' => $value,
      ];

      if (empty($value)) {
        // This level is empty, so there can be no sublevels.
        break;
      }
      $subdivision = $this->subdivisionRepository->get($value, $address->getLocale());
      if (!$subdivision) {
        // This level has no predefined subdivisions, stop.
        break;
      }

      // Replace the subdivision values with the predefined ones.
      $values[$field] = [
        'code' => $subdivision->getCode(),
        'name' => $subdivision->getName(),
      ];

      if (!$subdivision->hasChildren()) {
        // The current subdivision has no children, stop.
        break;
      }
    }

    return $values;
  }

}
