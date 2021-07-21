<?php

namespace Drupal\Tests\social_branding\Unit;

use Drupal\social_branding\PreferredPlatformFeature;
use Drupal\Tests\UnitTestCase;

/**
 * PreferredPlatformFeature test.
 *
 * @coversDefaultClass \Drupal\social_branding\PreferredPlatformFeature
 * @group social_branding
 */
class PreferredPlatformFeatureTest extends UnitTestCase {

  /**
   * @covers ::getName
   */
  public function testPreferredPlatformFeatureNameIsString(): void {
    $preferred_feature = new PreferredPlatformFeature('feature1', 1);

    $this->assertEquals('feature1', $preferred_feature->getName());
    $this->assertIsString($preferred_feature->getName());
  }

  /**
   * @covers ::getWeight
   */
  public function testPreferredPlatformFeatureNameIsInteger(): void {
    $preferred_feature = new PreferredPlatformFeature('feature1', 0);

    $this->assertEquals(0, $preferred_feature->getWeight());
    $this->assertIsInt($preferred_feature->getWeight());
  }

  /**
   * @covers ::setWeight
   */
  public function testPreferredPlatformFeatureCanChangeWeight(): void {
    $preferred_feature = new PreferredPlatformFeature('feature1', 1);

    $this->assertEquals(1, $preferred_feature->getWeight());

    $preferred_feature->setWeight(2);

    $this->assertEquals(2, $preferred_feature->getWeight());
  }

}
