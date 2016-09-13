<?php
/**
 * @file
 * Contains \Drupal\activity_send_email\ActivitySendEmailConfigOverride.
 */
namespace Drupal\activity_send_email;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Example configuration override.
 */
class ActivitySendEmailConfigOverride implements ConfigFactoryOverrideInterface {
  public function loadOverrides($names) {
    $overrides = array();

    // Set email destination to given message templates.
    $message_templates = ['create_post_profile'];
    foreach ($message_templates as $message_template) {
      $config_name = "message.template.{$message_template}";
      if (in_array($config_name, $names)) {
        $config = \Drupal::service('config.factory')->getEditable($config_name);
        $activity_destinations = $config->get('third_party_settings.activity_logger.activity_destinations');
        $activity_destinations['email'] = 'email';
        $overrides[$config_name] = ['third_party_settings' => ['activity_logger' => ['activity_destinations' => $activity_destinations]]];

      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ActivitySendEmailConfigOverride';
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
