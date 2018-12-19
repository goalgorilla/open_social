<?php

namespace Drupal\social_geolocation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Config override sapi social_all social_content social_groups social_users.
 */
class SocialGeolocationSearchApiConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns config overrides for Search API indexes for the Geolocation field.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   * @codingStandardsIgnoreStart
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_overrides = [
      'search_api.index.social_all',
      'search_api.index.social_groups',
      'search_api.index.social_content',
      'search_api.index.social_users',
    ];

    // For all the search api indexes add our specific geolocation index data.
    foreach ($config_overrides as $config_name) {
      if (in_array($config_name, $names)) {
        // Grab current configuration and push the new values.
        $config = $this->configFactory->getEditable($config_name);
        // We have to add config dependencies to field storage.
        $dependencies = $config->getOriginal('dependencies', FALSE)['config'];
        foreach ($this->getSocialExtraDependencies($config_name) as $key => $value) {
          $dependencies[] = $value;
        }

        $field_settings = $config->getOriginal('field_settings', FALSE);
        // We have to add field_settings to the geolocation fields.
        foreach ($this->getSocialExtraOverrideDependencies($config_name) as $key => $value) {
          $field_settings[$key] = $value;
        }

        // Make sure we keep the current config and add ours to it.
        $overrides[$config_name]['dependencies']['config'] = $dependencies;
        $overrides[$config_name]['field_settings'] = $field_settings;
      }
    }

    return $overrides;
  }

  /**
   * Get all the Dependencies needed to add to the search api index.
   *
   * @param string $name
   *   The name of the config file.
   *
   * @return array
   *   Array with the dependencies to merge in to the existing one.
   */
  public function getSocialExtraDependencies($name = '') {
    if (!empty($name)) {
      if ($name === 'search_api.index.social_groups') {
        return ['field.storage.group.field_group_geolocation'];
      }
      if ($name === 'search_api.index.social_content') {
        return ['field.storage.node.field_event_geolocation'];
      }
      if ($name === 'search_api.index.social_users') {
        return ['field.storage.profile.field_profile_geolocation'];
      }
      if ($name === 'search_api.index.social_all') {
        return [
          'field.storage.node.field_event_geolocation',
          'field.storage.group.field_group_geolocation',
          'field.storage.profile.field_profile_geolocation',
        ];
      }
    }

    return [];
  }

  /**
   * Return the field settings so we can add them to the correct config files.
   *
   * @param string $name
   *  The name of the config file.
   *
   * @return array|mixed
   *  Either an empty array or the YAML Decoded version of the array.
   */
  public function getSocialExtraOverrideDependencies($name = '') {
    if (!empty($name)) {
      if ($name === 'search_api.index.social_groups') {
        return $this->getSocialGroupsOverrides();
      }
      if ($name === 'search_api.index.social_content') {
        return $this->getSocialContentOverrides();
      }
      if ($name === 'search_api.index.social_users') {
        return $this->getSocialUserOverrides();
      }
      if ($name === 'search_api.index.social_all') {
        return $this->getSocialAllOverrides();
      }
    }

    return [];
  }

  public function getSocialAllOverrides() {
    $overrides = <<<YAML
node_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation
node_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation
node_lng_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation
group_type:
  label: 'Group type'
  datasource_id: 'entity:group'
  property_path: type
  type: string
group_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation
group_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation
group_lat_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation    
profile_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
profile_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
profile_lng_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
YAML;

    return Yaml::decode($overrides);
  }

  public function getSocialUserOverrides() {
    $overrides = <<<YAML
profile_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
profile_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
profile_lng_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:profile'
  property_path: 'field_profile_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.profile.field_profile_geolocation
YAML;

    return Yaml::decode($overrides);
  }

  public function getSocialGroupsOverrides() {
    $overrides = <<<YAML
group_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation
group_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation
group_lng_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:group'
  property_path: 'field_group_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.group.field_group_geolocation
YAML;

    return Yaml::decode($overrides);
  }

  public function getSocialContentOverrides() {
    $overrides = <<<YAML
node_lat_cos:
  label: 'Geolocation » Latitude cosine'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lat_cos'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation
node_lat_sin:
  label: 'Geolocation » Latitude sine'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lat_sin'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation  
node_lng_rad:
  label: 'Geolocation » Longitude radian'
  datasource_id: 'entity:node'
  property_path: 'field_event_geolocation:lng_rad'
  type: decimal
  dependencies:
    config:
      - field.storage.node.field_event_geolocation
YAML;

    return Yaml::decode($overrides);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGeolocationConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
