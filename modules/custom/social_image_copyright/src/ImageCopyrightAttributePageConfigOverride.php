<?php

namespace Drupal\social_image_copyright;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Override to add the advance image copyright attribute to the page field.
 */
class ImageCopyrightAttributePageConfigOverride implements ConfigFactoryOverrideInterface {

  protected const FIELD_TYPE = 'advance_image';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ImageCopyrightAttributePageConfigOverride constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Field configurations to override.
   *
   * @return array
   *   Field related configurations as an array.
   */
  private function configFieldOverrides() {
    return [
      // Page.
      'field.field.node.page.field_page_image' => 'field_page_image',
    ];
  }

  /**
   * Storage configurations to override.
   *
   * @return array
   *   Storage related configurations as an array.
   */
  private function configStorageOverrides() {
    return [
      'field.storage.node.field_page_image' => 'field_page_image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Update field storage configurations.
    $field_store_configs = $this->configStorageOverrides();
    foreach ($field_store_configs as $config_name => $field_name) {
      if (in_array($config_name, $names, TRUE)) {
        $this->addImageStorageOverride($config_name, $field_name, $overrides);
      }
    }

    // Update field configurations.
    $field_configurations = $this->configFieldOverrides();
    foreach ($field_configurations as $config_name => $field_name) {
      if (in_array($config_name, $names, TRUE)) {
        $this->addImageFieldOverride($config_name, $field_name, $overrides);
      }
    }

    return $overrides;
  }

  /**
   * Alters the image field definition.
   *
   * @param string $config_name
   *   The config name to override.
   * @param string $field_name
   *   The field to override.
   * @param array $overrides
   *   A configuration to override.
   */
  protected function addImageFieldOverride($config_name, $field_name, array &$overrides) {
    if (!empty($config_name) && !empty($field_name)) {
      // Add dependency to social_image_copyright module.
      $config = $this->configFactory->getEditable($config_name);
      $dependencies = $config->getOriginal('dependencies.module');
      $overrides[$config_name]['dependencies']['module'] = $dependencies;
      $overrides[$config_name]['dependencies']['module'][] = 'social_image_copyright';

      // Add copyright attribute field settings.
      $overrides[$config_name]['field_type'] = self::FIELD_TYPE;
      $overrides[$config_name]['settings']['default_image']['copyright'] = '';
      $overrides[$config_name]['settings']['image_copyright_field'] = 1;
      $overrides[$config_name]['settings']['image_copyright_field_required'] = 0;
    }
  }

  /**
   * Alters the image storage definition.
   *
   * @param string $config_name
   *   The config name to override.
   * @param string $field_name
   *   The field to override.
   * @param array $overrides
   *   A configuration to override.
   */
  protected function addImageStorageOverride($config_name, $field_name, array &$overrides) {
    if (!empty($config_name) && !empty($field_name)) {
      // Add dependency to social_image_copyright module.
      $config = $this->configFactory->getEditable($config_name);
      $dependencies = $config->getOriginal('dependencies.module');
      $overrides[$config_name]['dependencies']['module'] = $dependencies;
      $overrides[$config_name]['dependencies']['module'][] = 'social_image_copyright';

      $overrides[$config_name]['type'] = self::FIELD_TYPE;
      $overrides[$config_name]['settings']['default_image']['copyright'] = '';
      $overrides[$config_name]['module'] = 'social_image_copyright';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ImageCopyrightAttributePageConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
