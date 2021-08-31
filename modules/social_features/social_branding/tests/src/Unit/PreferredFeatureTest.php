<?php

namespace Drupal\Tests\social_branding\Unit;

use Drupal\social_branding\PreferredFeature;
use Drupal\Tests\UnitTestCase;

/**
 * PreferredFeature test.
 *
 * @coversDefaultClass \Drupal\social_branding\PreferredFeature
 * @group social_branding
 */
class PreferredFeatureTest extends UnitTestCase {

  /**
   * @covers ::getName
   */
  public function testPreferredFeatureNameIsString(): void {
    $preferred_feature = new PreferredFeature('feature1', 1);

    $this->assertEquals('feature1', $preferred_feature->getName());
    $this->assertIsString($preferred_feature->getName());
  }

  /**
   * @covers ::getWeight
   */
  public function testPreferredFeatureWeightIsInteger(): void {
    $preferred_feature = new PreferredFeature('feature1', 0);

    $this->assertEquals(0, $preferred_feature->getWeight());
    $this->assertIsInt($preferred_feature->getWeight());
  }

  /**
   * @covers ::setWeight
   */
  public function testPreferredFeatureCanChangeWeight(): void {
    $preferred_feature = new PreferredFeature('feature1', 1);

    $this->assertEquals(1, $preferred_feature->getWeight());

    $preferred_feature->setWeight(2);

    $this->assertEquals(2, $preferred_feature->getWeight());
  }

}
