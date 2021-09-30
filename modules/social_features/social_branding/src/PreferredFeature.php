<?php

namespace Drupal\social_branding;

/**
 * Defines the preferred feature class.
 */
class PreferredFeature implements PreferredFeatureInterface {

  /**
   * The preferred feature name.
   *
   * @var string
   */
  private string $name;

  /**
   * The preferred feature weight.
   *
   * @var int
   */
  private int $weight;

  /**
   * Create a new PreferredFeature instance.
   *
   * @param string $name
   *   The feature name as machine name. e.g. cool_feature.
   * @param int $weight
   *   An integer used to indicate ordering, with higher weights
   *   sinking: e.g. -1 will be above 0 and 1 will be below 0.
   */
  public function __construct(string $name, int $weight) {
    $this->name = $name;
    $this->weight = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() : string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() : int {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): self {
    $this->weight = $weight;
    return $this;
  }

}
