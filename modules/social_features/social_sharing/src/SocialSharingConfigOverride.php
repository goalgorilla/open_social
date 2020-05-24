<?php

namespace Drupal\social_sharing;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialSharingConfigOverride.
 *
 * @package Drupal\social_sharing
 */
class SocialSharingConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'block.block.shariffsharebuttons',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {

        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $request_path = $config->get('visibility.request_path');

        // If there is not request path, set the default options.
        if (empty($request_path)) {
          $request_path['id'] = 'request_path';
          $request_path['negate'] = TRUE;
          $request_path['context_mapping'] = [];
        }

        if (isset($request_path['pages'])) {
          if (!empty($request_path['pages'])) {
            $request_path['pages'] .= "\r\n" . '*/all-enrollment-requests/confirm-decline/*' . "\r\n" . '*/invite/email' . "\r\n" . '*/invite/user' . "\r\n" . '*/invite/confirm';
          }
          else {
            $request_path['pages'] = '*/all-enrollment-requests/confirm-decline/*' . "\r\n" . '*/invite/email' . "\r\n" . '*/invite/user' . "\r\n" . '*/invite/confirm';
          }
        }

        $overrides[$config_name] = ['visibility' => ['request_path' => $request_path]];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'EventEnrollmentConfigOverride';
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
