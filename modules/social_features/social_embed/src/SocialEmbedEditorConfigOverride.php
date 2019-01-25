<?php

namespace Drupal\social_embed;

use Drupal\Core\Config\Config;

/**
 * Class SocialEmbedEditorConfigOverride.
 *
 * @package Drupal\social_embed
 */
class SocialEmbedEditorConfigOverride extends SocialEmbedConfigOverrideBase {

  /**
   * {@inheritdoc}
   */
  public function doOverride(Config $config, $config_name, $convert_url, array &$overrides) {
    $settings = $config->get('settings');

    if (!($settings && isset($settings['toolbar']['rows']) && is_array($settings['toolbar']['rows']))) {
      return;
    }

    $button_exists = FALSE;

    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if ($button === 'social_embed') {
            $button_exists = TRUE;
            break;
          }
        }
      }
    }

    if (!$button_exists) {
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

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEmbedEditorConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return 'editor.editor';
  }

}
