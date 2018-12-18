<?php

namespace Drupal\social_geolocation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Configuration override for search_api social_all social_content social_groups
 * social_users.
 */
class SocialGeolocationSearchApiConfigOverride implements ConfigFactoryOverrideInterface {

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

    if (in_array('search_api.index.social_all', $names)) {
      $overrides['search_api.index.social_all'] = $this->getSocialAllOverrides();
    }

    if (in_array('search_api.index.social_groups', $names)) {
      $overrides['search_api.index.social_groups'] = $this->getSocialGroupsOverrides();
    }

    if (in_array('search_api.index.social_content', $names)) {
      $overrides['search_api.index.social_content'] = $this->getSocialContentOverrides();
    }

    if (in_array('search_api.index.social_users', $names)) {
      $overrides['search_api.index.social_users'] = $this->getSocialUserOverrides();
    }

    return $overrides;
  }


  public function getSocialAllOverrides() {
    $overrides = <<<YAML
dependencies:
  config:
    - field.storage.node.field_event_geolocation
    - field.storage.group.field_group_geolocation
    - field.storage.profile.field_profile_geolocation
field_settings:
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
  group_lng_rad:
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
dependencies:
  config:
    - field.storage.profile.field_profile_geolocation
field_settings:
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
dependencies:
  config:
    - field.storage.group.field_group_geolocation
field_settings:
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
dependencies:
  config:
    - field.storage.node.field_event_geolocation
field_settings:
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
