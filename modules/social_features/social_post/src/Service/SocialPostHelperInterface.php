<?php

namespace Drupal\social_post\Service;

/**
 * Social Post Helper Interface.
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
  public function buildCurrentUserImage(): ?array;

}
