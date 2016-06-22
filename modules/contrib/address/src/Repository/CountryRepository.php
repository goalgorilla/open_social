<?php

/**
 * @file
 * Contains \Drupal\address\Repository\CountryRepository.
 */

namespace Drupal\address\Repository;

use CommerceGuys\Intl\Country\CountryRepository as ExternalCountryRepository;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface as ExternalCountryRepositoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Locale\CountryManagerInterface;

/**
 * Defines the country repository.
 *
 * Countries are stored on disk in JSON and cached inside Drupal.
 */
class CountryRepository extends ExternalCountryRepository implements ExternalCountryRepositoryInterface, CountryManagerInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a CountryRepository instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    $this->cache = $cache;
    $this->languageManager = $language_manager;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function loadDefinitions($locale) {
    if (isset($this->definitions[$locale])) {
      return $this->definitions[$locale];
    }

    $cache_key = 'address.countries.' . $locale;
    if ($cached = $this->cache->get($cache_key)) {
      $this->definitions[$locale] = $cached->data;
    }
    else {
      $filename = $this->definitionPath . $locale . '.json';
      $this->definitions[$locale] = json_decode(file_get_contents($filename), TRUE);
      // Merge-in base definitions.
      $base_definitions = $this->loadBaseDefinitions();
      foreach ($this->definitions[$locale] as $countryCode => $definition) {
        $this->definitions[$locale][$countryCode] += $base_definitions[$countryCode];
      }
      $this->cache->set($cache_key, $this->definitions[$locale], CacheBackendInterface::CACHE_PERMANENT, ['countries']);
    }

    return $this->definitions[$locale];
  }

  /**
   * Loads the base country definitions.
   *
   * @return array
   */
  protected function loadBaseDefinitions() {
    if (!empty($this->baseDefinitions)) {
      return $this->baseDefinitions;
    }

    $cache_key = 'address.countries.base';
    if ($cached = $this->cache->get($cache_key)) {
      $this->baseDefinitions = $cached->data;
    }
    else {
      $this->baseDefinitions = json_decode(file_get_contents($this->definitionPath . 'base.json'), TRUE);
      $this->cache->set($cache_key, $this->baseDefinitions, CacheBackendInterface::CACHE_PERMANENT, ['countries']);
    }

    return $this->baseDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLocale() {
    return $this->languageManager->getConfigOverrideLanguage()->getId();
  }

}
