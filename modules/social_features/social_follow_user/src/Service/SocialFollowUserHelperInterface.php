<?php

namespace Drupal\social_follow_user\Service;

use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the helper service interface.
 */
interface SocialFollowUserHelperInterface {

  /**
   * Connect profile previewer to a specific element.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile entity object.
   * @param array $variables
   *   The preprocessing variables.
   * @param array|string $path
   *   (optional) The list of keys to the location of the attributes in
   *   preprocessing variables. Defaults to 'attributes'.
   */
  public function preview(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes'
  ): void;

}
