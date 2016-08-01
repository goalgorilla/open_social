<?php

namespace Drupal\address\Repository;

use CommerceGuys\Addressing\Repository\SubdivisionRepository as ExternalSubdivisionRepository;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the subdivision repository.
 *
 * Subdivisions are stored on disk in JSON and cached inside Drupal.
 */
class SubdivisionRepository extends ExternalSubdivisionRepository {

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
   * Creates a SubdivisionRepository instance.
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
  public function getDepth($countryCode) {
    if (empty($this->depths)) {
      $cache_key = 'address.subdivisions.depths';
      if ($cached = $this->cache->get($cache_key)) {
        $this->depths = $cached->data;
      }
      else {
        $filename = $this->definitionPath . 'depths.json';
        $this->depths = json_decode(file_get_contents($filename), TRUE);
        $this->cache->set($cache_key, $this->depths, CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      }
    }

    return isset($this->depths[$countryCode]) ? $this->depths[$countryCode] : 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function loadDefinitions($countryCode, $parentId = NULL) {
    $lookup_id = $parentId ?: $countryCode;
    if (isset($this->definitions[$lookup_id])) {
      return $this->definitions[$lookup_id];
    }

    // If there are predefined subdivisions at this level, try to load them.
    $this->definitions[$lookup_id] = [];
    if ($this->hasData($countryCode, $parentId)) {
      $cache_key = 'address.subdivisions.' . $lookup_id;
      $filename = $this->definitionPath . $lookup_id . '.json';
      if ($cached = $this->cache->get($cache_key)) {
        $this->definitions[$lookup_id] = $cached->data;
      }
      elseif ($raw_definition = @file_get_contents($filename)) {
        $this->definitions[$lookup_id] = json_decode($raw_definition, TRUE);
        $this->cache->set($cache_key, $this->definitions[$lookup_id], CacheBackendInterface::CACHE_PERMANENT, ['subdivisions']);
      }
    }

    return $this->definitions[$lookup_id];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLocale() {
    return $this->languageManager->getConfigOverrideLanguage()->getId();
  }

}
