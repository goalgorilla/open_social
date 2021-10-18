<?php

namespace Drupal\social_post_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides an overridden elements.
 *
 * @package Drupal\social_post_album
 */
class SocialPostPhotoAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Field widget or formatter type per configuration.
   */
  const TYPES = [
    'core.entity_form_display.post.photo.default' => 'social_post_album_image',
    'core.entity_view_display.post.photo.activity' => 'album_image',
  ];

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach (self::TYPES as $config_name => $type) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name]['content']['field_post_image']['type'] = $type;
      }
    }

    // See https://www.drupal.org/project/social/issues/3232278
    $config_name = 'core.entity_form_display.post.photo.group';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['content']['field_post_image']['settings']['preview_image_style'] = 'social_x_large';
    }

    // We want to change the image style for the post view page.
    if ($this->routeMatch->getRouteName() === 'entity.post.canonical') {
      $config_name = 'core.entity_view_display.post.photo.default';

      if (in_array($config_name, $names)) {
        $overrides[$config_name]['content']['field_post_image']['settings']['image_style'] = 'social_x_large';
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialPostPhotoAlbumConfigOverride';
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
