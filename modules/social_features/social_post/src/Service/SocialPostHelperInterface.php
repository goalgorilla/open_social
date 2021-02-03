<?php

namespace Drupal\social_post\Service;

/**
 * Interface SocialPostHelperInterface.
 *
 * @package Drupal\social_post\Service
 */
interface SocialPostHelperInterface {

  /**
   * Gets image of the user profile.
   *
   * @return array|null
   *   The renderable data.
   */
  public function buildCurrentUserImage();

}
