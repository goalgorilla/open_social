<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\PackageTest.
 */

namespace Drupal\Tests\features\Unit;

use Drupal\features\Package;

/**
 * @coversDefaultClass \Drupal\features\Package
 * @group features
 */
class PackageTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::setFeaturesInfo
   */
  public function testSetFeaturesInfo() {
    $package = new Package('test_feature', []);

    $this->assertEquals([], $package->getFeaturesInfo());
    $package->setFeaturesInfo(['bundle' => 'test_bundle']);
    $this->assertEquals('test_bundle', $package->getBundle());
    $this->assertEquals(['bundle' => 'test_bundle'], $package->getFeaturesInfo());
  }

  public function testGetConfig() {
    $package = new Package('test_feature', ['config' => ['test_config_a', 'test_config_b']]);
    $this->assertEquals(['test_config_a', 'test_config_b'], $package->getConfig());
    return $package;
  }

  /**
   * @depends testGetConfig
   * @covers ::appendConfig
   */
  public function testAppendConfig(Package $package) {
    $package->appendConfig('test_config_a');
    $package->appendConfig('test_config_c');

    $this->assertEquals(['test_config_a', 'test_config_b', 'test_config_c'], array_values($package->getConfig()));
    return $package;
  }

  /**
   * @depends testAppendConfig
   * @covers ::removeConfig
   */
  public function testRemoveConfig(Package $package) {
    $package->removeConfig('test_config_a');

    $this->assertEquals(['test_config_b', 'test_config_c'], array_values($package->getConfig()));
  }

}
