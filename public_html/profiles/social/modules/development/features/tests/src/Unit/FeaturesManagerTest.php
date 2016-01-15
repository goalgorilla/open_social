<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\FeaturesManagerTest.
 */

namespace Drupal\Tests\features\Unit;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityType;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesManager;
use Drupal\features\FeaturesManagerInterface;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\features\FeaturesManager
 * @group features
 */
class FeaturesManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $entity_type = $this->getMock('\Drupal\Core\Config\Entity\ConfigEntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('custom');
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->entityManager->expects($this->any())
      ->method('getDefinition')
      ->willReturn($entity_type);
    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $config_manager = $this->getMock('Drupal\Core\Config\ConfigManagerInterface');
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->featuresManager = new FeaturesManager($this->entityManager, $config_factory, $storage, $config_manager, $module_handler);

    $string_translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('string_translation', $string_translation);
    $container->set('app.root', $this->root);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getActiveStorage
   */
  public function testGetActiveStorage() {
    $this->assertInstanceOf('\Drupal\Core\Config\StorageInterface', $this->featuresManager->getActiveStorage());
  }

  /**
   * @covers ::getExtensionStorages
   */
  public function testGetExtensionStorages() {
    $this->assertInstanceOf('\Drupal\features\FeaturesExtensionStoragesInterface', $this->featuresManager->getExtensionStorages());
  }

  /**
   * @covers ::getFullName
   * @dataProvider providerTestGetFullName
   */
  public function testGetFullName($type, $name, $expected) {
    $this->assertEquals($this->featuresManager->getFullName($type, $name), $expected);
  }

  /**
   * Data provider for ::testGetFullName().
   */
  public function providerTestGetFullName() {
    return [
      [NULL, 'name', 'name'],
      [FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG, 'name', 'name'],
      ['custom', 'name', 'custom.name'],
    ];
  }

  /**
   * @covers ::getPackage
   * @covers ::getPackages
   * @covers ::reset
   * @covers ::setPackages
   */
  public function testPackages() {
    $packages = ['foo' => 'bar'];
    $this->featuresManager->setPackages($packages);
    $this->assertEquals($packages, $this->featuresManager->getPackages());
    $this->assertEquals('bar', $this->featuresManager->getPackage('foo'));
    $this->featuresManager->reset();
    $this->assertArrayEquals([], $this->featuresManager->getPackages());
    $this->assertNull($this->featuresManager->getPackage('foo'));
  }

  /**
   * @covers ::setConfigCollection
   * @covers ::getConfigCollection
   */
  public function testConfigCollection() {
    $config = ['config' => new ConfigurationItem('', [])];
    $this->featuresManager->setConfigCollection($config);
    $this->assertArrayEquals($config, $this->featuresManager->getConfigCollection());
  }

  /**
   * @covers ::setPackage
   */
  public function testSetPackage() {
    // @todo
  }

  protected function getAssignInterPackageDependenciesConfigCollection() {
    $config_collection = [];
    $config_collection['example.config'] = (new ConfigurationItem('example.config', [
      'dependencies' => [
        'config' => [
          'example.config2',
          'example.config3',
        ],
      ],
    ]))->setPackage('package');
    $config_collection['example.config2'] =  (new ConfigurationItem('example.config2', [
      'dependencies' => [],
    ]))
      ->setPackage('package2')
      ->setProvidingFeature('my_feature');
    $config_collection['example.config3'] = (new ConfigurationItem('example.config3', [
      'dependencies' => [],
    ]))
      ->setProvidingFeature('my_other_feature');
    return $config_collection;
  }

  /**
   * @covers ::assignInterPackageDependencies
   */
  public function testAssignInterPackageDependenciesWithoutBundle() {
    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $bundle = $this->prophesize(FeaturesBundleInterface::class);
    // Provide a bundle without any prefix.
    $bundle->getFullName('package')->willReturn('package');
    $bundle->getFullName('package2')->willReturn('package2');
    $assigner->getBundle('')->willReturn($bundle->reveal());
    $this->featuresManager->setAssigner($assigner->reveal());

    $this->featuresManager->setConfigCollection($this->getAssignInterPackageDependenciesConfigCollection());

    $packages = [
      'package' => [
        'machine_name' => 'package',
        'config' => ['example.config', 'example.config3'],
        'dependencies' => [],
        'bundle' => '',
      ],
      'package2' => [
        'machine_name' => 'package2',
        'config' => ['example.config2'],
        'dependencies' => [],
        'bundle' => '',
      ],
    ];

    $expected = $packages;
    // example.config3 has a providing_feature but no assigned package.
    $expected['package']['dependencies'][] = 'my_other_feature';
    // my_package2 provides configuration required by configuration in
    // my_package.
    // Because package assignments take precedence over providing_feature ones,
    // package2 should have been assigned rather than my_feature.
    $expected['package']['dependencies'][] = 'package2';
    $this->featuresManager->setPackages($packages);

    $this->featuresManager->assignInterPackageDependencies($packages);
    $this->assertEquals($expected, $packages);
  }

  /**
   * @covers ::assignInterPackageDependencies
   */
  public function testAssignInterPackageDependenciesWithBundle() {
    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $bundle = $this->prophesize(FeaturesBundleInterface::class);
    // Provide a bundle without any prefix.
    $bundle->getFullName('package')->willReturn('package');
    $bundle->getFullName('package2')->willReturn('package2');
    $assigner->getBundle('giraffe')->willReturn($bundle->reveal());
    $this->featuresManager->setAssigner($assigner->reveal());

    $this->featuresManager->setConfigCollection($this->getAssignInterPackageDependenciesConfigCollection());

    $packages = [
      'package' => [
        'machine_name' => 'package',
        'config' => ['example.config', 'example.config3'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ],
      'package2' => [
        'machine_name' => 'package2',
        'config' => ['example.config2'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ],
    ];

    $expected = $packages;
    // example.config3 has a providing_feature but no assigned package.
    $expected['package']['dependencies'][] = 'my_other_feature';
    // my_package2 provides configuration required by configuration in
    // my_package.
    // Because package assignments take precedence over providing_feature ones,
    // package2 should have been assigned rather than my_feature.
    $expected['package']['dependencies'][] = 'package2';
    $this->featuresManager->setPackages($packages);

    $this->featuresManager->assignInterPackageDependencies($packages);
    $this->assertEquals($expected, $packages);
  }

  /**
   * @covers ::reset
   */
  public function testReset() {
    $packages = [
      'package' => [
        'machine_name' => 'package',
        'config' => ['example.config', 'example.config3'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ],
      'package2' => [
        'machine_name' => 'package2',
        'config' => ['example.config2'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ],
    ];
    $this->featuresManager->setPackages($packages);

    $config_item = new ConfigurationItem('example', [], ['package' => 'package']);
    $config_item2 = new ConfigurationItem('example2', [], ['package' => 'package2']);
    $this->featuresManager->setConfigCollection([$config_item, $config_item2]);

    $this->featuresManager->reset();
    $this->assertEmpty($this->featuresManager->getPackages());
    $config_collection = $this->featuresManager->getConfigCollection();
    $this->assertEquals('', $config_collection[0]->getPackage());
    $this->assertEquals('', $config_collection[1]->getPackage());
  }

}
