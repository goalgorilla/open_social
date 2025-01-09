<?php

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\address\Repository\CountryRepository;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the Address formatter.
 *
 * @FieldFormatter(
 *   id = "address_formatter",
 *   label = @Translation("Address Formatted"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressFormatter extends FormatterBase {

  /**
   * The country repository from address module.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected $countryRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a AddressFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
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
   * @param \Drupal\address\Repository\CountryRepository $country_repository
   *   Country repository from address module.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    CountryRepository $country_repository,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->countryRepository = $country_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('address.country_repository'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Displays the address formatted.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $address_config = $this->configFactory->get('social_core.address.settings');
    $address_string = $address_config->get('format');
    $address_values = current($items->getValue());

    // When the address field is empty, return early.
    if (empty($address_values)) {
      return [];
    }

    foreach ($address_values as $key => $value) {
      if (empty($value)) {
        continue;
      }

      $address_value = match ($key) {
        'country_code' => $this->countryRepository->get($value)->getName(),
        default => $value,
      };

      $address_string = str_replace("@" . $key . "%", $address_value, $address_string);
    }

    return ['#markup' => preg_replace($address_config->get('regex_pattern'), '', $address_string)];
  }

}
