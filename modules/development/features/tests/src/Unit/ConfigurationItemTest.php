<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\ConfigurationItemTest.
 */

namespace Drupal\Tests\features\Unit;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesManagerInterface;

/**
 * @coversDefaultClass \Drupal\features\ConfigurationItem
 * @group features
 */
class ConfigurationItemTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::fromConfigStringToConfigType
   */
  public function testFromConfigStringToConfigType() {
    $this->assertEquals('system.simple', ConfigurationItem::fromConfigStringToConfigType(FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG));
    $this->assertEquals('node', ConfigurationItem::fromConfigStringToConfigType('node'));
  }

}
