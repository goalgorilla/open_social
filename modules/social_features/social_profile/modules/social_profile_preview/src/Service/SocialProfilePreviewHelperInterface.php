<?php

namespace Drupal\social_profile_preview\Service;

use Drupal\profile\Entity\ProfileInterface;

/**
 * Defines the helper service interface.
 */
interface SocialProfilePreviewHelperInterface {

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
   * @param bool $return_as_object
   *   (optional) TRUE if attributes set should be returned as an object even if
   *   it was an array. Defaults to FALSE.
   * @param string|null $base_field
   *   (optional) The key name of the sub-element contains the base content of
   *   the element. Defaults to NULL.
   * @param string|null $extra_field
   *   (optional) The key name of the sub-element that can contain an
   *   organization tag or other extra content. Defaults to NULL.
   */
  public function alter(
    ProfileInterface $profile,
    array &$variables,
    $path = 'attributes',
    bool $return_as_object = FALSE,
    string $base_field = NULL,
    string $extra_field = NULL
  ): void;

}
