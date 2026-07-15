<?php

declare(strict_types=1);

namespace Drupal\social_core\FeatureFlag;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;

/**
 * Discovers feature flag definitions from *.feature_flags.yml files.
 */
final class FeatureFlagDiscovery {

  /**
   * Cache ID prefix for discovered definitions.
   */
  private const CACHE_PREFIX = 'social_core.feature_flags.definitions';

  /**
   * Constructs a FeatureFlagDiscovery object.
   */
  public function __construct(
    private readonly CacheBackendInterface $cache,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly FeatureFlagState $featureFlagState,
  ) {}

  /**
   * Returns all discovered feature flag definitions.
   *
   * @return array<string, \Drupal\social_core\FeatureFlag\FeatureFlagDefinition>
   *   Definitions keyed by machine name.
   */
  public function getDefinitions(): array {
    $deployment_identifier = (string) Settings::get('deployment_identifier', '');
    $cache_id = self::CACHE_PREFIX . ':' . $deployment_identifier;

    if ($cache = $this->cache->get($cache_id)) {
      return $this->buildDefinitionsFromEntries($cache->data);
    }

    $entries = $this->discoverDefinitionEntries();
    $definitions = $this->buildDefinitionsFromEntries($entries);
    $tags = ['feature_flag:list'];
    foreach (array_keys($definitions) as $machine_name) {
      $tags[] = 'feature_flag:' . $machine_name;
    }

    $this->cache->set($cache_id, $entries, Cache::PERMANENT, $tags);
    $this->featureFlagState->prune(array_keys($definitions));

    return $definitions;
  }

  /**
   * Returns all raw discovered feature flag entries.
   *
   * @return list<\Drupal\social_core\FeatureFlag\FeatureFlagDefinitionEntry>
   *   Raw definition entries.
   */
  public function getDefinitionEntries(): array {
    $deployment_identifier = (string) Settings::get('deployment_identifier', '');
    $cache_id = self::CACHE_PREFIX . ':' . $deployment_identifier;

    if ($cache = $this->cache->get($cache_id)) {
      return $cache->data;
    }

    $this->getDefinitions();
    $cache = $this->cache->get($cache_id);
    return $cache ? $cache->data : $this->discoverDefinitionEntries();
  }

  /**
   * Discovers feature flag entries from YAML files.
   *
   * @return list<\Drupal\social_core\FeatureFlag\FeatureFlagDefinitionEntry>
   *   Raw definition entries.
   */
  private function discoverDefinitionEntries(): array {
    $yaml_discovery = new YamlDiscovery('feature_flags', $this->moduleHandler->getModuleDirectories());
    $entries = [];

    foreach ($yaml_discovery->findAll() as $provider => $flags) {
      if (!is_array($flags)) {
        continue;
      }

      foreach ($flags as $machine_name => $definition) {
        $entries[] = new FeatureFlagDefinitionEntry((string) $machine_name, $provider, $definition);
      }
    }

    return $entries;
  }

  /**
   * Builds keyed definitions from raw entries.
   *
   * @param list<\Drupal\social_core\FeatureFlag\FeatureFlagDefinitionEntry> $entries
   *   Raw definition entries.
   *
   * @return array<string, \Drupal\social_core\FeatureFlag\FeatureFlagDefinition>
   *   Valid definitions keyed by machine name.
   */
  private function buildDefinitionsFromEntries(array $entries): array {
    $definitions = [];

    foreach ($entries as $entry) {
      if (isset($definitions[$entry->machineName])) {
        continue;
      }

      try {
        $definitions[$entry->machineName] = FeatureFlagDefinition::fromRaw(
          $entry->machineName,
          $entry->provider,
          $entry->raw,
        );
      }
      catch (\InvalidArgumentException) {
        continue;
      }
    }

    return $definitions;
  }

}
