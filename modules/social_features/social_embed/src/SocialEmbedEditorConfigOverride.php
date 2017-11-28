<?php

namespace Drupal\social_embed;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialEmbedEditorConfigOverride.
 *
 * @package Drupal\social_embed
 */
class SocialEmbedEditorConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $config_names = [
      'editor.editor.basic_html',
      'editor.editor.full_html',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        /* @var \Drupal\Core\Config\ConfigFactory $config */
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        if ($settings = $config->get('settings')) {
          if (isset($settings['toolbar']['rows']) && is_array($settings['toolbar']['rows'])) {
            $button_exists = FALSE;
            foreach ($settings['toolbar']['rows'] as $row_id => $row) {
              foreach ($row as $group_id => $group) {
                foreach ($group['items'] as $button_id => $button) {
                  if ($button === 'social_embed') {
                    $button_exists = TRUE;
                  }
                }
              }
            }
            if ($button_exists === FALSE) {
              $row_array_keys = array_keys($settings['toolbar']['rows']);
              $last_row_key = end($row_array_keys);

              $group = [];
              $group['name'] = 'Embed';
              $group['items'] = [];
              $group['items'][] = 'social_embed';
              $settings['toolbar']['rows'][$last_row_key][] = $group;
              $overrides[$config_name] = [
                'settings' => $settings,
              ];
            }
          }
        }
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEmbedEditorConfigOverride';
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
