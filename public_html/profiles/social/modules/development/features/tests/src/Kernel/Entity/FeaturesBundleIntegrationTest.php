<?php

namespace Drupal\Tests\features\Kernel\Entity;

use Drupal\features\Entity\FeaturesBundle;
use Drupal\features\FeaturesBundleInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\features\Entity\FeaturesBundle
 * @group features
 */
class FeaturesBundleIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules  = ['features'];

  public function testCrud() {
    $bundle = FeaturesBundle::create([
      'machine_name' => 'test',
      'name' => 'Test',
    ]);
    $bundle->save();

    /** @var \Drupal\features\Entity\FeaturesBundle $bundle */
    $bundle = FeaturesBundle::load('test');
    $this->assertEquals('Test', $bundle->getName());
  }

  /**
   * @covers ::isDefault
   */
  public function testIsDefaultWithDefaultBundle() {
    $bundle = FeaturesBundle::create([
      'machine_name' => FeaturesBundleInterface::DEFAULT_BUNDLE,
    ]);

    $this->assertTrue($bundle->isDefault());
  }

  /**
   * @covers ::isDefault
   */
  public function testIsDefaultWithNonDefaultBundle() {
    $bundle = FeaturesBundle::create([
      'machine_name' => 'other',
    ]);

    $this->assertFalse($bundle->isDefault());
  }

  /**
   * @covers ::getFullName
   */
  public function testGetFullName() {
  }

  /**
   * @covers ::getShortName
   */
  public function testGetShortName() {
  }

  /**
   * @covers ::getProfileName
   * @covers ::setProfileName
   */
  public function testGetProfile() {
    $bundle = FeaturesBundle::create([
      'machine_name' => 'other',
      'profile_name' => 'example',
      'is_profile' => TRUE,
    ]);
    $this->assertEquals('example', $bundle->getProfileName());

    $bundle->setProfileName('example2');
    $this->assertEquals('example2', $bundle->getProfileName());
  }

}
