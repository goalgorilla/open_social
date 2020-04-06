<?php

namespace Drupal\social_event_invite;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialEventInviteConfigOverride.
 *
 * @package Drupal\social_event_invite
 */
class SocialEventInviteConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];
    // Remove the page title block for event invites.
    $config_names = [
      'block.block.socialbase_pagetitleblock_content',
      'block.block.socialblue_pagetitleblock_content',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {

        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $request_path = $config->get('visibility.request_path');
        $request_path = $request_path['pages'] . "\r\n" . '*/event-invites';

        $overrides[$config_name] = ['visibility' => ['request_path' => ['pages' => $request_path]]];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialEventInviteConfigOverride';
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
