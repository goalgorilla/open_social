<?php

namespace Drupal\social_post_album;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class SocialPostPhotoAlbumConfigOverride.
 *
 * @package Drupal\social_post_album
 */
class SocialPostPhotoAlbumConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The current route match.
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
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    // @codingStandardsIgnoreEnd
    $overrides = [];

    $config_name = 'core.entity_form_display.post.photo.default';
    $route_name = $this->routeMatch->getRouteName();

    if (in_array($config_name, $names) && $route_name !== 'entity.post.edit_form') {
      $overrides[$config_name]['content']['field_post_image']['settings']['preview_image_style'] = 'social_x_large';
    }

    $config_name = 'core.entity_view_display.post.photo.activity';

    if (in_array($config_name, $names)) {
      $overrides[$config_name]['content']['field_post_image']['type'] = 'album_image';
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
