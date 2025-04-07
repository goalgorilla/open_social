<?php

namespace Drupal\social_profile;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupType;

/**
 * Group Affiliation service.
 */
class GroupAffiliation {

  const AFFILIATION_CANDIDATE_CONFIG_KEY = 'affiliation_candidate';
  const AFFILIATION_ENABLED_CONFIG_KEY = 'affiliation_enabled';

  // Cache tag that invalidates all caches related to group affiliation.
  const GENERAL_CACHE_TAG = 'group_affiliation_options_by_user';

  /**
   * Group Affiliation constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cacheBackend,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Affiliation feature status.
   *
   * This is status for affiliation feature in general, which is manually set.
   *
   * Method cache_id:
   *  affiliation_feature_is_enabled.
   *
   * Cache tags:
   *  group_affiliation_options_by_user.
   *
   * @return bool
   *   Return TRUE affiliation feature is enabled.
   */
  public function isAffiliationFeatureEnabled(): bool {
    $cache_id = 'affiliation_feature_is_enabled';
    $cache = $this->cacheBackend->get($cache_id);
    if ($cache !== FALSE) {

      // Return cached data.
      return $cache->data;
    }

    $config = $this->configFactory->get('social_profile.settings');
    $result = (bool) $config->get('group_affiliation_status');

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    $this->cacheBackend->set($cache_id, $result, Cache::PERMANENT, $cacheability->getCacheTags());

    return $result;
  }

  /**
   * Group affiliation status.
   *
   * This is status for group affiliation part only, which is automatically set
   * based on condition below. This status is used to show/hide the group
   * affiliation field in user profile. Group affiliation can not exist without
   * affiliation feature enabled. Affiliation feature without group affiliation
   * enabled means that users will be able to use non-platform affiliation
   * without group affiliations.
   *
   * Condition:
   *  - There should be at least one group type affiliation candidate enabled.
   *
   * Method cache_id:
   *  group_affiliation_is_enabled.
   *
   * Cache tags:
   *  group_affiliation_options_by_user.
   *
   * @return bool
   *   Return TRUE if group affiliation is enabled.
   */
  public function isGroupAffiliationEnabled(): bool {
    $cache_id = 'group_affiliation_is_enabled';
    $cache = $this->cacheBackend->get($cache_id);
    if ($cache !== FALSE) {

      // Return cached data.
      return $cache->data;
    }

    $number_of_group_types = count($this->getAffiliationEnabledGroupTypes());
    $result = $number_of_group_types >= 1;

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    $this->cacheBackend->set($cache_id, $result, Cache::PERMANENT, $cacheability->getCacheTags());

    return $result;
  }

  /**
   * Get affiliation candidate group types.
   *
   * Method cache_id:
   *  group_affiliation_candidates.
   *
   * Cache tags:
   *  group_affiliation_options_by_user
   *
   * @return array<string, GroupType>
   *   An array of group types.
   */
  public function getAffiliationCandidateGroupTypes(): array {
    $cache_id = 'group_affiliation_candidates';
    $cache = $this->cacheBackend->get($cache_id);
    if ($cache !== FALSE) {

      // Return cached data.
      return $cache->data;
    }

    /** @var array<string, \Drupal\group\Entity\GroupType> $group_types */
    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();

    // Get all the group types with configuration property that indicates that
    // given group is affiliation candidate (AFFILIATION_CANDIDATE_CONFIG_KEY).
    $filtered = array_filter($group_types, function ($group_type) {
      /** @var \Drupal\group\Entity\GroupType $group_type */
      return $group_type->getThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY) === TRUE;
    });

    // Sort alphabetically by group type name.
    uasort($filtered, function ($group_type_a, $group_type_b) {
      return strcmp((string) $group_type_a->label(), (string) $group_type_b->label());
    });

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    $this->cacheBackend->set($cache_id, $filtered, Cache::PERMANENT, $cacheability->getCacheTags());

    return $filtered;
  }

  /**
   * Get affiliation enabled group types.
   *
   * Group affiliation can be enabled only for groups that are affiliation
   * candidates. Even if group affiliation is somehow enabled for given group,
   * without given group being affiliation candidate, this will not list the
   * group in the results.
   *
   * Method cache_id:
   *  group_affiliation_enabled
   *
   * Cache tags:
   *  group_affiliation_options_by_user
   *
   * @return array<string, GroupType>
   *   An array of group types in alphabetic order.
   */
  public function getAffiliationEnabledGroupTypes(): array {
    $cache_id = 'group_affiliation_enabled';
    $cache = $this->cacheBackend->get($cache_id);
    if ($cache !== FALSE) {

      // Return cached data.
      return $cache->data;
    }

    $candidates = $this->getAffiliationCandidateGroupTypes();

    $filtered = array_filter($candidates, function (GroupType $group_type) {
      return $group_type->getThirdPartySetting('social_profile', self::AFFILIATION_ENABLED_CONFIG_KEY) === TRUE;
    });

    $cacheability = (new CacheableMetadata())
      ->setCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    $this->cacheBackend->set($cache_id, $filtered, Cache::PERMANENT, $cacheability->getCacheTags());

    return $filtered;
  }

}
