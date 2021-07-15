<?php

namespace Drupal\social_branding;

/**
 * Provides an interface defining a preferred platform feature.
 */
interface PreferredPlatformFeatureInterface {

  /**
   * Get the name of the preferred platform feature.
   *
   * @return string
   *   The preferred platform feature name.
   */
  public function getName() : string;

  /**
   * Get the weight of the preferred platform feature.
   *
   * @return int
   *   The preferred platform feature weight.
   */
  public function getWeight() : int;

  /**
   * Set the weight of this preferred platform feature.
   *
   * @param int $weight
   *   An integer with the weight of this preferred platform feature.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setWeight(int $weight) : self;

}
