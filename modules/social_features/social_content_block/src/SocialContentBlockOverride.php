<?php

namespace Drupal\social_content_block;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialContentBlockOverride.
 *
 * Override content block form.
 *
 * @package Drupal\social_content_block
 */
class SocialContentBlockOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The content block plugin definitions.
   *
   * @var array
   */
  protected $definitions = NULL;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    if (\Drupal::hasService('plugin.manager.content_block')) {
      $this->definitions = \Drupal::service('plugin.manager.content_block')->getDefinitions();
    }
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_name = 'field.field.paragraph.block.field_block_reference_secondary';

    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);

      $settings = $config->getOriginal('settings', FALSE)['plugin_ids'];

      // Get all the blocks from this custom block type.
      $storage = self::getBlockContent();
      $query = $storage->getQuery()
        ->condition('type', 'custom_content_list');
      $ids = $query->execute();

      foreach ($ids as $id) {
        $block = BlockContent::load($id);
        if ($block) {
          $plugin_ids[] = 'block_content:' . $block->uuid();
        }
      }

      // Add the blocks to the landing page.
      if (isset($plugin_ids)) {
        foreach ($plugin_ids as $plugin_id) {
          $settings[$plugin_id] = $plugin_id;
        }
      }

      $overrides[$config_name]['settings']['plugin_ids'] = $settings;
    }

    if (!$this->definitions) {
      return $overrides;
    }

    $config_name = 'core.entity_form_display.block_content.custom_content_list.default';

    if (in_array($config_name, $names)) {
      $config = $this->configFactory->getEditable($config_name);
      $dependencies = $config->get('dependencies.config');
      $group = $config->get('third_party_settings.field_group.group_filter_options.children');
      $fields = [];
      $field_config_prefix = 'field.field.block_content.custom_content_list.';
      $field_configs = $this->configFactory->listAll($field_config_prefix);

      foreach ($this->definitions as $plugin_definition) {
        // It's set in a six because weights from zero to five are reserved by
        // other fields such as the plugin ID field and plugin filters field.
        $weight = 6;

        foreach ($plugin_definition['fields'] as $field) {
          $field_config = $field_config_prefix . $field;

          if (in_array($field_config, $field_configs)) {
            $dependencies[] = $field_config;
            $group[] = $field;

            $fields[$field] = [
              'weight' => $weight++,
              'settings' => [
                'match_operator' => 'CONTAINS',
                'size' => 60,
                'placeholder' => '',
                'match_limit' => 10,
              ],
              'third_party_settings' => [],
              'type' => 'entity_reference_autocomplete_tags',
              'region' => 'content',
            ];
          }
        }
      }

      $overrides[$config_name] = [
        'dependencies' => [
          'config' => $dependencies,
        ],
        'third_party_settings' => [
          'field_group' => [
            'group_filter_options' => [
              'children' => $group,
            ],
          ],
        ],
        'content' => $fields,
      ];
    }

    $config_name = 'core.entity_view_display.block_content.custom_content_list.default';

    if (in_array($config_name, $names)) {
      $field_config_prefix = 'field.field.block_content.custom_content_list.';
      $field_configs = $this->configFactory->listAll($field_config_prefix);

      foreach ($this->definitions as $plugin_definition) {
        foreach ($plugin_definition['fields'] as $field) {
          $field_config = $field_config_prefix . $field;

          if (in_array($field_config, $field_configs)) {
            $overrides[$config_name]['hidden'][$field] = TRUE;
          }
        }
      }
    }

    return $overrides;
  }

  /**
   * Load the config pages that exist.
   *
   * Use a static method instead of dependency injection to avoid circular
   * dependencies.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Keyed array of block_content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getBlockContent() {
    return \Drupal::entityTypeManager()
      ->getStorage('block_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialContentBlockOverride';
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
