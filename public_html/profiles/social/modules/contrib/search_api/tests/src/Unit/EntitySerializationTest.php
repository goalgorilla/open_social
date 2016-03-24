<?php

/**
 * @file
 * Contains \Drupal\Tests\search_api\Unit\EntitySerializationTest.
 */

namespace Drupal\Tests\search_api\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the serialization of the entities.
 *
 * @group search_api
 */
class EntitySerializationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $config = $this->getMockBuilder('Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $mock_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $mock_factory->method('get')->willReturn($config);
    $container = new ContainerBuilder();
    $container->set('config.factory', $mock_factory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests that serialization of index entities doesn't lead to data loss.
   */
  public function testIndexSerialization() {
    // As our test index, just use the one from the DB Defaults module.
    $path = __DIR__ . '/../../../search_api_db/search_api_db_defaults/config/optional/search_api.index.default_index.yml';
    $index_values = Yaml::decode(file_get_contents($path));
    $index = new Index($index_values, 'search_api_index');

    /** @var \Drupal\search_api\IndexInterface $serialized */
    $serialized = unserialize(serialize($index));

    $this->assertNotEmpty($serialized);
    $this->assertEquals($index, $serialized);
  }
}
