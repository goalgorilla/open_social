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
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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
      $query = \Drupal::entityQuery('block_content')
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

    return $overrides;
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
