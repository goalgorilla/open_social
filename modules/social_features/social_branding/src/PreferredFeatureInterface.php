<?php

namespace Drupal\social_branding;

/**
 * Provides an interface defining a preferred feature.
 */
interface PreferredFeatureInterface {

  /**
   * Get the name of the preferred feature.
   *
   * @return string
   *   The preferred feature name.
   */
  public function getName() : string;

  /**
   * Get the weight of the preferred feature.
   *
   * @return int
   *   The preferred feature weight.
   */
  public function getWeight() : int;

  /**
   * Set the weight of this preferred feature.
   *
   * @param int $weight
   *   An integer used to indicate ordering, with higher weights
   *   sinking: e.g. -1 will be above 0 and 1 will be below 0.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setWeight(int $weight) : self;

}
