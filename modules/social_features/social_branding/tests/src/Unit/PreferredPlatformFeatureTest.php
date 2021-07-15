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
   * The preferred platform feature.
   *
   * @var \Drupal\social_branding\PreferredPlatformFeature
   */
  protected $preferredPlatformFeature;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->preferredPlatformFeature = new PreferredPlatformFeature('feature1', 1);
  }

  /**
   * @covers ::getName
   */
  public function testPreferredPlatformFeatureNameIsString(): void {
    $this->assertEquals('feature1', $this->preferredPlatformFeature->getName());
    $this->assertIsString($this->preferredPlatformFeature->getName());
  }

  /**
   * @covers ::getWeight
   */
  public function testPreferredPlatformFeatureNameIsInteger(): void {
    $this->assertEquals(1, $this->preferredPlatformFeature->getWeight());
    $this->assertIsInt($this->preferredPlatformFeature->getWeight());
  }

  /**
   * @covers ::setWeight
   */
  public function testPreferredPlatformFeatureCanChangeWeight(): void {
    $this->assertEquals(1, $this->preferredPlatformFeature->getWeight());

    $this->preferredPlatformFeature->setWeight(2);

    $this->assertEquals(2, $this->preferredPlatformFeature->getWeight());
  }

}
