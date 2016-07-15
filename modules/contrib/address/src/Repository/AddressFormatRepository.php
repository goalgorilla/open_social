<?php

namespace Drupal\address\Repository;

use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\Language;

/**
 * Defines the address format repository.
 *
 * Address formats are stored as config entities.
 */
class AddressFormatRepository implements AddressFormatRepositoryInterface {

  /**
   * The address format storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $formatStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates an AddressFormatRepository instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->formatStorage = $entity_type_manager->getStorage('address_format');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get($countryCode, $locale = NULL) {
    if ($locale) {
      $original_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $locale]));
      $address_format = $this->formatStorage->load($countryCode);
      $this->languageManager->setConfigOverrideLanguage($original_language);
    }
    else {
      $address_format = $this->formatStorage->load($countryCode);
    }

    if (!$address_format) {
      // No format found for the given country code, fallback to ZZ.
      $address_format = $this->formatStorage->load('ZZ');
    }

    return $address_format;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($locale = NULL) {
    if ($locale) {
      $original_language = $this->languageManager->getConfigOverrideLanguage();
      $this->languageManager->setConfigOverrideLanguage(new Language(['id' => $locale]));
      $address_formats = $this->formatStorage->loadMultiple();
      $this->languageManager->setConfigOverrideLanguage($original_language);
    }
    else {
      $address_formats = $this->formatStorage->loadMultiple();
    }

    return $address_formats;
  }

}
