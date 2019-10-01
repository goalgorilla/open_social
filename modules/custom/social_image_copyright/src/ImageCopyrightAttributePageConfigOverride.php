<?php

namespace Drupal\social_image_copyright;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Override to add the advanced image copyright attribute to the page field.
 */
class ImageCopyrightAttributePageConfigOverride implements ConfigFactoryOverrideInterface {

  protected const FIELD_TYPE = 'advanced_image';

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
      // Book pages.
      'field.field.node.book.field_book_image' => 'field_book_image',
      // Events.
      'field.field.node.event.field_event_image' => 'field_event_image',
      // Landing pages.
      'field.field.paragraph.hero.field_hero_image' => 'field_hero_image',
      'field.field.paragraph.hero_small.field_hero_small_image' => 'field_hero_small_image',
      'field.field.paragraph.featured_item.field_featured_item_image' => 'field_featured_item_image',
      // Pages.
      'field.field.node.page.field_page_image' => 'field_page_image',
      // Topics.
      'field.field.node.topic.field_topic_image' => 'field_topic_image',
      // Groups.
      'field.field.group.open_group.field_group_image' => 'field_group_image',
      'field.field.group.closed_group.field_group_image' => 'field_group_image',
      'field.field.group.public_group.field_group_image' => 'field_group_image',
      'field.field.group.flexible_group.field_group_image' => 'field_group_image',
      'field.field.group.secret_group.field_group_image' => 'field_group_image',
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
      // Book pages.
      'field.storage.node.field_book_image' => 'field_book_image',
      // Events.
      'field.storage.node.field_event_image' => 'field_event_image',
      // Landing pages.
      'field.storage.paragraph.field_hero_image' => 'field_hero_image',
      'field.storage.paragraph.field_hero_small_image' => 'field_hero_small_image',
      'field.storage.paragraph.field_featured_item_image' => 'field_featured_item_image',
      // Pages.
      'field.storage.node.field_page_image' => 'field_page_image',
      // Topics.
      'field.storage.node.field_topic_image' => 'field_topic_image',
      // Groups.
      'field.storage.group.field_group_image' => 'field_group_image',
    ];
  }

  /**
   * Form displays to override.
   *
   * @return array
   *   Display related configurations to override.
   */
  private function configDisplayOverrides() {
    return [
      // Book pages.
      'core.entity_form_display.node.book.default' => 'field_book_image',
      // Events.
      'core.entity_form_display.node.event.default' => 'field_event_image',
      // Landing pages.
      'core.entity_view_display.paragraph.hero.default' => 'field_hero_image',
      'core.entity_view_display.paragraph.hero_small.default' => 'field_hero_small_image',
      'core.entity_view_display.paragraph.featured_item.default' => 'field_featured_item_image',
      // Pages.
      'core.entity_form_display.node.page.default' => 'field_page_image',
      // Topics.
      'core.entity_form_display.node.topic.default' => 'field_topic_image',
      // Groups.
      'core.entity_form_display.group.open_group.default' => 'field_group_image',
      'core.entity_form_display.group.closed_group.default' => 'field_group_image',
      'core.entity_form_display.group.public_group.default' => 'field_group_image',
      'core.entity_form_display.group.flexible_group.default' => 'field_group_image',
      'core.entity_form_display.group.secret_group.default' => 'field_group_image',
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

    // Update the form display configurations.
    $form_display_configs = $this->configDisplayOverrides();
    foreach ($form_display_configs as $config_name => $field_name) {
      if (in_array($config_name, $names, TRUE)) {
        $this->addImageDisplayOverride($config_name, $field_name, $overrides);
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
      $this->addModuleDependencies($config_name, $overrides);

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
      $this->addModuleDependencies($config_name, $overrides);

      // Add copyright attribute field settings.
      $overrides[$config_name]['type'] = self::FIELD_TYPE;
      $overrides[$config_name]['settings']['default_image']['copyright'] = '';
      $overrides[$config_name]['module'] = 'social_image_copyright';
    }
  }

  /**
   * Alters the form display widget.
   *
   * @param string $config_name
   *   The config name to override.
   * @param string $field_name
   *   The field to override.
   * @param array $overrides
   *   A configuration to override.
   */
  protected function addImageDisplayOverride($config_name, $field_name, array &$overrides) {
    if (!empty($config_name) && !empty($field_name)) {
      // Add dependency to social_image_copyright module.
      $this->addModuleDependencies($config_name, $overrides);

      // Add copyright attribute field settings.
      $overrides[$config_name]['content'][$field_name]['copyright_attribute'] = 1;
      $overrides[$config_name]['content'][$field_name]['type'] = 'image_crop_copyright_attribute';
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

  /**
   * Get and update the module dependencies.
   *
   * @param string $config_name
   *   The config name to override.
   * @param array $overrides
   *   A configuration to override.
   */
  private function addModuleDependencies($config_name, array &$overrides) {
    $config = $this->configFactory->getEditable($config_name);
    $dependencies = $config->getOriginal('dependencies.module');
    $overrides[$config_name]['dependencies']['module'] = $dependencies;
    $overrides[$config_name]['dependencies']['module'][] = 'social_image_copyright';
  }

}
