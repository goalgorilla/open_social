<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\FeaturesManagerTest.
 */

namespace Drupal\Tests\features\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_update\ConfigDiffInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\features\Entity\FeaturesBundle;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\ConfigurationItem;
use Drupal\features\FeaturesExtensionStoragesInterface;
use Drupal\features\FeaturesManager;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\Package;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

/**
 * @coversDefaultClass Drupal\features\FeaturesManager
 * @group features
 */
class FeaturesManagerTest extends UnitTestCase {
  /**
   * @var string
   *   The name of the install profile.
   */
  const PROFILE_NAME = 'my_profile';

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configStorage;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

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
    $this->configFactory = $this->getMock(ConfigFactoryInterface::class);
    $this->configStorage = $this->getMock(StorageInterface::class);
    $this->configManager = $this->getMock(ConfigManagerInterface::class);
    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);
    $this->featuresManager = new FeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);

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
          'example.config4',
        ],
      ],
    ]))->setPackage('package');

    $config_collection['example.config2'] =  (new ConfigurationItem('example.config2', [
      'dependencies' => [],
    ]))
      ->setPackage('package2')
      ->setProvider('my_feature');
    $config_collection['example.config3'] = (new ConfigurationItem('example.config3', [
      'dependencies' => [],
    ]))
      ->setProvider('my_other_feature');
    $config_collection['example.config4'] = (new ConfigurationItem('example.config3', [
      'dependencies' => [],
    ]))
      ->setProvider(static::PROFILE_NAME);
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
    $bundle->isDefault()->willReturn(TRUE);
    $assigner->getBundle('')->willReturn($bundle->reveal());
    // Use the wrapper because we need ::drupalGetProfile().
    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);
    $features_manager->setAssigner($assigner->reveal());

    $features_manager->setConfigCollection($this->getAssignInterPackageDependenciesConfigCollection());

    $packages = [
      'package' => new Package('package', [
        'config' => ['example.config', 'example.config3'],
        'dependencies' => [],
        'bundle' => '',
      ]),
      'package2' => new Package('package2', [
        'config' => ['example.config2'],
        'dependencies' => [],
        'bundle' => '',
      ]),
    ];

    $features_manager->setPackages($packages);
    // Dependencies require the full package names.
    $package_names = array_keys($packages);
    $features_manager->setPackageBundleNames($bundle->reveal(), $package_names);
    $packages = $features_manager->getPackages();
    $features_manager->assignInterPackageDependencies($bundle->reveal(), $packages);
    // example.config3 has a providing_feature but no assigned package.
    // my_package2 provides configuration required by configuration in
    // my_package.
    // Because package assignments take precedence over providing_feature ones,
    // package2 should have been assigned rather than my_feature.
    $this->assertEquals(['my_other_feature', 'package2'], $packages['package']->getDependencies());
    $this->assertEquals([], $packages['package2']->getDependencies());
  }

  /**
   * @covers ::assignInterPackageDependencies
   */
  public function testAssignInterPackageDependenciesWithBundle() {
    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $bundle = $this->prophesize(FeaturesBundleInterface::class);
    // Provide a bundle without any prefix.
    $bundle->getFullName('package')->willReturn('giraffe_package');
    $bundle->getFullName('package2')->willReturn('giraffe_package2');
    $bundle->getFullName('giraffe_package')->willReturn('giraffe_package');
    $bundle->getFullName('giraffe_package2')->willReturn('giraffe_package2');
    $bundle->isDefault()->willReturn(FALSE);
    $bundle->getMachineName()->willReturn('giraffe');
    $assigner->getBundle('giraffe')->willReturn($bundle->reveal());
    // Use the wrapper because we need ::drupalGetProfile().
    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);
    $features_manager->setAssigner($assigner->reveal());
    $features_manager->setConfigCollection($this->getAssignInterPackageDependenciesConfigCollection());

    $packages = [
      'package' => new Package('package', [
        'config' => ['example.config'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ]),
      'package2' => new Package('package2', [
        'config' => ['example.config2'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ]),
    ];

    $features_manager->setPackages($packages);
    // Dependencies require the full package names.
    $package_names = array_keys($packages);
    $features_manager->setPackageBundleNames($bundle->reveal(), $package_names);
    $packages = $features_manager->getPackages();
    $features_manager->assignInterPackageDependencies($bundle->reveal(), $packages);
    // example.config3 has a providing_feature but no assigned package.
    // my_package2 provides configuration required by configuration in
    // my_package.
    // Because package assignments take precedence over providing_feature ones,
    // package2 should have been assigned rather than my_feature.
    $expected = ['giraffe_package2', 'my_other_feature'];
    $this->assertEquals($expected, $packages['giraffe_package']->getDependencies());
  }

  /**
   * @covers ::assignInterPackageDependencies
   * @expectedException \Exception
   * @expectedExceptionMessage The packages have not yet been prefixed with a bundle name
   */
  public function testAssignInterPackageDependenciesPrematureCall() {
    $bundle = $this->prophesize(FeaturesBundleInterface::class);
    $packages = [
      'package' => new Package('package', [
        'config' => ['example.config', 'example.config3'],
        'dependencies' => [],
        'bundle' => 'giraffe',
      ]),
    ];

    $this->featuresManager->assignInterPackageDependencies($bundle->reveal(), $packages);
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


  /**
   * @covers ::detectMissing
   */
  public function testDetectMissing() {
    $package = new Package('test-package', [
      'configOrig' => ['test_config', 'test_config_non_existing'],
    ]);

    $config_collection = [];
    $config_collection['test_config'] = new ConfigurationItem('test_config', []);
    $this->featuresManager->setConfigCollection($config_collection);

    $this->assertEquals(['test_config_non_existing'], $this->featuresManager->detectMissing($package));
  }

  /**
   * @covers ::detectOverrides
   */
  public function testDetectOverrides() {
    $config_diff = $this->prophesize(ConfigDiffInterface::class);
    $config_diff->same(Argument::cetera())->will(function($args) {
      return $args[0] == $args[1];
    });
    \Drupal::getContainer()->set('config_update.config_diff', $config_diff->reveal());

    $package = new Package('test-package', [
      'config' => ['test_config', 'test_overridden'],
    ]);

    $config_storage = $this->prophesize(StorageInterface::class);
    $config_storage->read('test_config')->willReturn([
      'key' => 'value',
    ]);
    $config_storage->read('test_overridden')->willReturn([
      'key2' => 'value2',
    ]);

    $extension_storage = $this->prophesize(FeaturesExtensionStoragesInterface::class);
    $extension_storage->read('test_config')->willReturn([
      'key' => 'value',
    ]);
    $extension_storage->read('test_overridden')->willReturn([
      'key2' => 'value0',
    ]);


    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $config_storage->reveal(), $this->configManager, $this->moduleHandler);
    $features_manager->setExtensionStorages($extension_storage->reveal());

    $this->assertEquals(['test_overridden'], $features_manager->detectOverrides($package));
  }

  /**
   * @covers ::assignConfigPackage
   */
  public function testAssignConfigPackageWithNonProviderExcludedConfig() {
    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $bundle = $this->prophesize(FeaturesBundleInterface::class);
    $bundle->isProfilePackage('test_package')->willReturn(FALSE);
    $assigner->getBundle(NULL)->willReturn($bundle->reveal());
    $this->featuresManager->setAssigner($assigner->reveal());

    $config_collection = [
      'test_config' => new ConfigurationItem('test_config', []),
      'test_config2' => new ConfigurationItem('test_config2', [
        'dependencies' => [
          'module' => ['example'],
        ]
      ], [
        'subdirectory' => InstallStorage::CONFIG_INSTALL_DIRECTORY,
      ]),
    ];
    $this->featuresManager->setConfigCollection($config_collection);

    $package = new Package('test_package');
    $this->featuresManager->setPackage($package);

    $this->featuresManager->assignConfigPackage('test_package', ['test_config', 'test_config2']);

    $this->assertEquals(['test_config', 'test_config2'], $this->featuresManager->getPackage('test_package')->getConfig());
    $this->assertEquals(['example'], $this->featuresManager->getPackage('test_package')->getDependencies());
  }

  /**
   * @covers ::assignConfigPackage
   */
  public function testAssignConfigPackageWithProviderExcludedConfig() {
    $config_collection = [
      'test_config' => new ConfigurationItem('test_config', []),
      'test_config2' => new ConfigurationItem('test_config2', [], ['providerExcluded' => TRUE]),
    ];
    $this->featuresManager->setConfigCollection($config_collection);

    $feature_assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $feature_assigner->getBundle(NULL)->willReturn(new FeaturesBundle(['machine_name' => 'default'], 'features_bundle'));
    $this->featuresManager->setAssigner($feature_assigner->reveal());

    $package = new Package('test_package');
    $original_package = clone $package;

    $this->featuresManager->setPackage($package);
    $this->featuresManager->assignConfigPackage('test_package', ['test_config', 'test_config2']);
    $this->assertEquals(['test_config'], $this->featuresManager->getPackage('test_package')->getConfig(), 'just assign new packages');

    $this->featuresManager->setPackage($original_package);
    $this->featuresManager->assignConfigPackage('test_package', ['test_config', 'test_config2'], TRUE);
    $this->assertEquals(['test_config', 'test_config2'], $this->featuresManager->getPackage('test_package')->getConfig(), 'just assign new packages');
  }

  /**
   * @covers ::initPackageFromExtension
   * @covers ::getPackageObject
   */
  public function testInitPackageFromNonInstalledExtension() {
    $extension = new Extension($this->root, 'module', 'modules/test_module/test_module.info.yml');

    $info_parser = $this->prophesize(InfoParserInterface::class);
    $info_parser->parse($this->root . '/modules/test_module/test_module.info.yml')->willReturn([
      'name' => 'Test module',
      'description' => 'test description',
      'type' => 'module',
    ]);
    \Drupal::getContainer()->set('info_parser', $info_parser->reveal());

    $bundle = $this->prophesize(FeaturesBundle::class);
    $bundle->getShortName('test_module')->willReturn('test_module');
    $bundle->isDefault()->willReturn(TRUE);

    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $assigner->findBundle(Argument::cetera())->willReturn($bundle->reveal());
    $this->featuresManager->setAssigner($assigner->reveal());

    $result = $this->featuresManager->initPackageFromExtension($extension);
    $this->assertInstanceOf(Package::class, $result);
    // Ensure that that calling the function twice works.
    $result = $this->featuresManager->initPackageFromExtension($extension);
    $this->assertInstanceOf(Package::class, $result);

    $this->assertEquals('test_module', $result->getMachineName());
    $this->assertEquals('Test module', $result->getName());
    $this->assertEquals('test description', $result->getDescription());
    $this->assertEquals('module', $result->getType());

    $this->assertEquals(FeaturesManagerInterface::STATUS_UNINSTALLED, $result->getStatus());
  }

  /**
   * @covers ::initPackageFromExtension
   * @covers ::getPackageObject
   */
  public function testInitPackageFromInstalledExtension() {
    $extension = new Extension($this->root, 'module', 'modules/test_module/test_module.info.yml');

    $info_parser = $this->prophesize(InfoParserInterface::class);
    $info_parser->parse($this->root . '/modules/test_module/test_module.info.yml')->willReturn([
      'name' => 'Test module',
      'description' => 'test description',
      'type' => 'module',
    ]);
    \Drupal::getContainer()->set('info_parser', $info_parser->reveal());

    $bundle = $this->prophesize(FeaturesBundle::class);
    $bundle->getShortName('test_module')->willReturn('test_module');
    $bundle->isDefault()->willReturn(TRUE);

    $assigner = $this->prophesize(FeaturesAssignerInterface::class);
    $assigner->findBundle(Argument::cetera())->willReturn($bundle->reveal());
    $this->featuresManager->setAssigner($assigner->reveal());

    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with('test_module')
      ->willReturn(TRUE);

    $result = $this->featuresManager->initPackageFromExtension($extension);
    $this->assertEquals(FeaturesManagerInterface::STATUS_INSTALLED, $result->getStatus());
  }

  public function testDetectNewWithNoConfig() {
    $package = new Package('test_feature');

    $this->assertEmpty($this->featuresManager->detectNew($package));
  }

  public function testDetectNewWithNoNewConfig() {
    $package = new Package('test_feature', ['config' => ['test_config']]);

    $extension_storage = $this->prophesize(FeaturesExtensionStoragesInterface::class);
    $extension_storage->read('test_config')->willReturn([
      'key' => 'value',
    ]);

    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);
    $features_manager->setExtensionStorages($extension_storage->reveal());

    $this->assertEmpty($features_manager->detectNew($package));
  }

  public function testDetectNewWithNewConfig() {
    $package = new Package('test_feature', ['config' => ['test_config']]);

    $extension_storage = $this->prophesize(FeaturesExtensionStoragesInterface::class);
    $extension_storage->read('test_config')->willReturn(FALSE);

    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);
    $features_manager->setExtensionStorages($extension_storage->reveal());

    $this->assertEquals(['test_config'], $features_manager->detectNew($package));
  }

  /**
   * @todo This could have of course much more test coverage.
   *
   * @covers ::mergeInfoArray
   *
   * @dataProvider providerTestMergeInfoArray
   */
  public function testMergeInfoArray($expected, $info1, $info2, $keys = []) {
    $this->assertSame($expected, $this->featuresManager->mergeInfoArray($info1, $info2, $keys));
  }

  public function providerTestMergeInfoArray() {
    $data = [];
    $data['empty-info'] = [[], [], []];
    $data['override-info'] = [
      ['name' => 'New name', 'core' => '8.x'],
      ['name' => 'Old name', 'core' => '8.x'],
      ['name' => 'New name']
    ];
    $data['dependency-merging'] = [
      ['dependencies' => ['a', 'b', 'c', 'd', 'e']],
      ['dependencies' => ['b', 'd', 'c']],
      ['dependencies' => ['a', 'b', 'e']],
      [],
    ];

    return $data;
  }

  public function testInitPackageWithNewPackage() {
    $bundle = new FeaturesBundle(['machine_name' => 'default'], 'features_bundle');

    $features_manager = new TestFeaturesManager($this->root, $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);
    $features_manager->setAllModules([]);

    $package = $features_manager->initPackage('test_feature', 'test name', 'test description', 'module', $bundle);

    $this->assertInstanceOf(Package::class, $package);
    $this->assertEquals('test_feature', $package->getMachineName());
    $this->assertEquals('test name', $package->getName());
    $this->assertEquals('test description', $package->getDescription());
    $this->assertEquals('module', $package->getType());
    $this->assertEquals('', $package->getBundle());
    $this->assertEquals([], $package->getFeaturesInfo());
  }

  public function testInitPackageWithExistingPackage() {
    $bundle = new FeaturesBundle(['machine_name' => 'default'], 'features_bundle');

    $features_manager = new TestFeaturesManager('vfs://drupal', $this->entityManager, $this->configFactory, $this->configStorage, $this->configManager, $this->moduleHandler);

    vfsStream::setup('drupal');
    \Drupal::getContainer()->set('app.root', 'vfs://drupal');
    vfsStream::create([
      'modules' => [
        'test_feature' => [
          'test_feature.info.yml' => <<<EOT
name: Test feature 2
type: module
core: 8.x
description: test description 2
EOT
      ,
          'test_feature.features.yml' => <<<EOT
true
EOT
          ,
        ],
      ],
    ]);
    $extension = new Extension('vfs://drupal', 'module', 'modules/test_feature/test_feature.info.yml');
    $features_manager->setAllModules(['test_feature' => $extension]);

    $this->moduleHandler->expects($this->any())
      ->method('exists')
      ->with('test_feature')
      ->willReturn(TRUE);

    $info_parser = new InfoParser();
    \Drupal::getContainer()->set('info_parser', $info_parser);

    $package = $features_manager->initPackage('test_feature', 'test name', 'test description', 'module', $bundle);

    $this->assertInstanceOf(Package::class, $package);
    $this->assertEquals(TRUE, $package->getFeaturesInfo());
  }

  /**
   * @covers ::prepareFiles
   */
  public function testPrepareFiles() {
    $packages = [];
    $packages['test_feature'] = new Package('test_feature', [
      'config' => ['test_config'],
      'name' => 'Test feature',
    ]);

    $config_collection = [];
    $config_collection['test_config'] = new ConfigurationItem('test_config', ['foo' => 'bar']);

    $this->featuresManager->setConfigCollection($config_collection);
    $this->featuresManager->prepareFiles($packages);

    $files = $packages['test_feature']->getFiles();
    $this->assertCount(3, $files);
    $this->assertEquals('test_feature.info.yml', $files['info']['filename']);
    $this->assertEquals(Yaml::encode([
      'name' => 'Test feature',
      'type' => 'module',
      'core' => '8.x',
    ]), $files['info']['string']);
    $this->assertEquals(Yaml::encode(TRUE), $files['features']['string']);

    $this->assertEquals('test_config.yml', $files['test_config']['filename']);
    $this->assertEquals(Yaml::encode([
      'foo' => 'bar'
    ]), $files['test_config']['string']);

    $this->assertEquals('test_feature.features.yml', $files['features']['filename']);
    $this->assertEquals(Yaml::encode(TRUE), $files['features']['string']);
  }

  /**
   * @covers ::getExportInfo
   */
  public function testGetExportInfoWithoutBundle() {
    $config_factory = $this->getConfigFactoryStub([
      'features.settings' => [
        'export' => [
          'folder' => 'custom',
        ],
      ],
    ]);
    $this->featuresManager = new FeaturesManager($this->root, $this->entityManager, $config_factory, $this->configStorage, $this->configManager, $this->moduleHandler);

    $package = new Package('test_feature');
    $result = $this->featuresManager->getExportInfo($package);

    $this->assertEquals(['test_feature', 'modules/custom'], $result);
  }

  /**
   * @covers ::getExportInfo
   */
  public function testGetExportInfoWithBundle() {
    $config_factory = $this->getConfigFactoryStub([
      'features.settings' => [
        'export' => [
          'folder' => 'custom',
        ],
      ],
    ]);
    $this->featuresManager = new FeaturesManager($this->root, $this->entityManager, $config_factory, $this->configStorage, $this->configManager, $this->moduleHandler);

    $package = new Package('test_feature');
    $bundle = new FeaturesBundle(['machine_name' => 'test_bundle'], 'features_bundle');

    $result = $this->featuresManager->getExportInfo($package, $bundle);

    $this->assertEquals(['test_bundle_test_feature', 'modules/custom'], $result);
  }

}

class TestFeaturesManager extends FeaturesManager {

  protected $allModules;

  /**
   * @param \Drupal\features\FeaturesExtensionStoragesInterface $extensionStorages
   */
  public function setExtensionStorages($extensionStorages) {
    $this->extensionStorages = $extensionStorages;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllModules() {
    if (isset($this->allModules)) {
      return $this->allModules;
    }
    return parent::getAllModules();
  }

  /**
   * @param mixed $all_modules
   */
  public function setAllModules($all_modules) {
    $this->allModules = $all_modules;
    return $this;
  }

  protected function drupalGetProfile() {
    return FeaturesManagerTest::PROFILE_NAME;
  }

}
