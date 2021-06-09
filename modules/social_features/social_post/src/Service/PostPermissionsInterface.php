<?php

namespace Drupal\social_post\Service;

/**
 * Interface PostPermissionsInterface.
 *
 * @package Drupal\social_post\Service
 */
interface PostPermissionsInterface {

  /**
   * Generate post permissions for all post types.
   *
   * @return array
   *   The post type permissions.
   */
  public function permissions();

}
