<?php

namespace Drupal\address;

use CommerceGuys\Addressing\Repository\AddressFormatRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Default implementation of the address format importer.
 */
class AddressFormatImporter implements AddressFormatImporterInterface {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The library's address format repository.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $externalRepository;

  /**
   * Constructs a AddressFormatImporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->storage = $entity_type_manager->getStorage('address_format');
    $this->languageManager = $language_manager;
    $this->externalRepository = new AddressFormatRepository();
  }

  /**
   * {@inheritdoc}
   */
  public function importAll() {
    $address_formats = $this->externalRepository->getAll();
    $country_codes = array_keys($address_formats);
    // It's nicer API-wise to just pass the country codes.
    // The external repository maintains a static cache, so the repeated ->get()
    // calls have minimal performance impact.
    $this->importEntities($country_codes);

    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $this->importTranslations(array_keys($languages));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importEntities(array $country_codes) {
    foreach ($country_codes as $country_code) {
      $address_format = $this->externalRepository->get($country_code);
      $values = [
        'langcode' => 'en',
        'countryCode' => $address_format->getCountryCode(),
        'format' => $address_format->getFormat(),
        'requiredFields' => $address_format->getRequiredFields(),
        'uppercaseFields' => $address_format->getUppercaseFields(),
        'administrativeAreaType' => $address_format->getAdministrativeAreaType(),
        'localityType' => $address_format->getLocalityType(),
        'dependentLocalityType' => $address_format->getDependentLocalityType(),
        'postalCodeType' => $address_format->getPostalCodeType(),
        'postalCodePattern' => $address_format->getPostalCodePattern(),
        'postalCodePrefix' => $address_format->getPostalCodePrefix(),
      ];
      $entity = $this->storage->create($values);
      $entity->trustData()->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importTranslations(array $langcodes) {
    $available_translations = $this->getAvailableTranslations();
    $available_translations = array_intersect_key($available_translations, array_flip($langcodes));
    foreach ($available_translations as $langcode => $country_codes) {
      $address_formats = $this->storage->loadMultiple($country_codes);
      foreach ($address_formats as $country_code => $address_format) {
        $external_translation = $this->externalRepository->get($country_code, $langcode);
        $config_name = $address_format->getConfigDependencyName();
        $config_translation = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
        $config_translation->set('format', $external_translation->getFormat());
        $config_translation->save();
      }
    }
  }

  /**
   * Gets the available library translations.
   *
   * @return array
   *   Array keyed by language code who's value is an array of country codes
   *   related to that language.
   */
  protected function getAvailableTranslations() {
    // Hardcoded for now, since the library has no method for getting this data.
    $translations = [
      'ja' => ['JP'],
      'ko' => ['KR'],
      'th' => ['TH'],
      'zh' => ['MO', 'CN'],
      'zh-hant' => ['HK', 'TW'],
    ];

    return $translations;
  }

}
