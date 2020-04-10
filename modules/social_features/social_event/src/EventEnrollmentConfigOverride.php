<?php

namespace Drupal\social_event;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class EventEnrollmentConfigOverride.
 *
 * @package Drupal\social_event
 */
class EventEnrollmentConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritDoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_names = [
      'block.block.socialbase_pagetitleblock',
      'block.block.socialblue_pagetitleblock',
      'block.block.views_block__managers_event_managers_2',
      'block.block.socialblue_views_block__event_enrollments_event_enrollments_socialbase',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {

        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $request_path = $config->get('visibility.request_path');

        if (!empty($request_path)) {
          $request_path['pages'] .= "\r\n";
        }

        $request_path['id'] = 'request_path';
        $request_path['negate'] = TRUE;
        $request_path['context_mapping'] = [];
        $request_path['pages'] .= '*/all-enrollment-requests/confirm-decline/*';

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
