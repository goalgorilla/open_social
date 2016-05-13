<?php

namespace Drupal\Tests\features\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\features\ConfigurationItem;

/**
 * @group features
 */
class FeaturesAssignTest extends KernelTestBase {

  const PACKAGE_NAME = 'my_test_package';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['features', 'node', 'system', 'user'];

  /**
   * @var \Drupal\features\FeaturesManager
   */
  protected $featuresManager;

  /**
   * @var \Drupal\features\FeaturesAssigner
   */
  protected $assigner;

  /**
   * @todo Remove the disabled strict config schema checking.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('features');
    $this->installConfig('system');
    \Drupal::configFactory()->getEditable('features.settings')
      ->set('assignment.enabled', [])
      ->set('bundle.settings', [])
      ->save();

    $this->featuresManager = \Drupal::service('features.manager');
    $this->assigner = \Drupal::service('features_assigner');

    // Start with an empty configuration collection.
    $this->featuresManager->setConfigCollection([]);
  }

  /**
   * @covers Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentBaseType
   */
  public function testAssignBase() {
    $method_id = 'base';

    // Enable the method.
    $this->enableAssignmentMethod($method_id);

    // Test the default options for the base assignment method.

    // Test node type assignments.
    // Declare the node_type entity 'article'.
    $this->addConfigurationItem('node.type.article', [], [
      'shortName' => 'article',
      'label' => 'Article',
      'type' => 'node_type',
      'dependents' => ['field.field.node.article.body'],
    ]);

    // Add a piece of dependent configuration.
    $this->addConfigurationItem('field.field.node.article.body', [], [
      'shortName' => 'node.article.body',
      'label' => 'Body',
      'type' => 'field_config',
      'dependents' => [],
    ]);

    $this->assigner->applyAssignmentMethod($method_id);

    $packages = $this->featuresManager->getPackages();

    $expected_package_names = ['article', 'user'];

    $this->assertEquals($expected_package_names, array_keys($packages), 'Expected packages not created.');  

    $expected_config_items = [
      'node.type.article',
      'field.field.node.article.body',
    ];

    $this->assertEquals($expected_config_items, $packages['article']->getConfig(), 'Expected configuration items not present in article package.');

  }

  /**
   * @covers Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentCoreType
   */
  public function testAssignCore() {
    $method_id = 'core';

    // Enable the method.
    $this->enableAssignmentMethod($method_id);

    // Test the default options for the core assignment method.

    // Add a piece of configuration of a core type.
    $this->addConfigurationItem('field.storage.node.body', [], [
      'shortName' => 'node.body',
      'label' => 'node.body',
      'type' => 'field_storage_config',
      'dependents' => ['field.field.node.article.body'],
    ]);

    // Add a piece of configuration of a non-core type.
    $this->addConfigurationItem('field.field.node.article.body', [], [
      'shortName' => 'node.article.body',
      'label' => 'Body',
      'type' => 'field_config',
      'dependents' => [],
    ]);

    $this->assigner->applyAssignmentMethod($method_id);

    $packages = $this->featuresManager->getPackages();

    $expected_package_names = ['core'];

    $this->assertEquals($expected_package_names, array_keys($packages), 'Expected packages not created.');  

    $this->assertTrue(in_array('field.storage.node.body', $packages['core']->getConfig(), 'Expected configuration item not present in core package.'));
    $this->assertFalse(in_array('field.field.node.article.body', $packages['core']->getConfig(), 'Unexpected configuration item present in core package.'));

  }

  /**
   * @covers Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentDependency
   */
  public function testAssignDependency() {
    $method_id = 'dependency';

    // Enable the method.
    $this->enableAssignmentMethod($method_id);

    // Test the default options for the base assignment method.

    // Test node type assignments.
    // Declare the node_type entity 'article'.
    $this->addConfigurationItem('node.type.article', [], [
      'shortName' => 'article',
      'label' => 'Article',
      'type' => 'node_type',
      'dependents' => ['field.field.node.article.body'],
    ]);

    // Add a piece of dependent configuration.
    $this->addConfigurationItem('field.field.node.article.body', [], [
      'shortName' => 'node.article.body',
      'label' => 'Body',
      'type' => 'field_config',
      'dependents' => [],
    ]);

    $this->featuresManager->initPackage(self::PACKAGE_NAME, 'My test package');
    $this->featuresManager->assignConfigPackage(self::PACKAGE_NAME, ['node.type.article']);

    $this->assigner->applyAssignmentMethod($method_id);

    $packages = $this->featuresManager->getPackages();

    $expected_package_names = [self::PACKAGE_NAME];

    $this->assertEquals($expected_package_names, array_keys($packages), 'Expected packages not created.');

    $expected_config_items = [
      'node.type.article',
      'field.field.node.article.body',
    ];

    $this->assertEquals($expected_config_items, $packages[self::PACKAGE_NAME]->getConfig(), 'Expected configuration items not present in article package.');

  }

  /**
   * @covers Drupal\features\Plugin\FeaturesAssignment\FeaturesAssignmentForwardDependency
   */
  public function testAssignForwardDependency() {
    $method_id = 'forward_dependency';

    // Enable the method.
    $this->enableAssignmentMethod($method_id);

    // Add some configuration.
    // Two parent items.
    $this->addConfigurationItem('parent1', [], [
      'type' => 'node_type',
      'dependents' => ['grandparent'],
    ]);
    $this->addConfigurationItem('parent2', [], [
      'type' => 'node_type',
      'dependents' => [],
    ]);
    // Something that belongs to just one parent.
    $this->addConfigurationItem('child1', [], [
      'type' => 'node_type',
      'dependents' => ['parent1'],
    ]);
    // Something that belongs to both parents.
    $this->addConfigurationItem('child2', [], [
      'type' => 'node_type',
      'dependents' => ['parent1', 'parent2'],
    ]);
    // Something that indirectly belongs to parent1.
    $this->addConfigurationItem('grandchild', [], [
      'type' => 'node_type',
      'dependents' => ['child1'],
    ]);
    // A dependent, not a dependency.
    $this->addConfigurationItem('grandparent', [], [
      'type' => 'node_type',
      'dependents' => [],
    ]);
    // Something completely unrelated.
    $this->addConfigurationItem('stranger', [], [
      'type' => 'node_type',
      'dependents' => [],
    ]);

    $this->featuresManager->initPackage(self::PACKAGE_NAME, 'My test package');
    $this->featuresManager->assignConfigPackage(self::PACKAGE_NAME, ['parent1']);

    $other_package_name = 'other_package';
    $this->featuresManager->initPackage($other_package_name, 'Other package');
    $this->featuresManager->assignConfigPackage($other_package_name, ['parent2']);

    $this->assigner->applyAssignmentMethod($method_id);

    $packages = $this->featuresManager->getPackages();
    $expected_package_names = [self::PACKAGE_NAME, $other_package_name];
    sort($expected_package_names);
    $actual_package_names = array_keys($packages);
    sort($actual_package_names);
    $this->assertEquals($expected_package_names, $actual_package_names, 'Expected packages not created.');

    $expected_config_items = [
      'parent1',
      'child1',
      'grandchild',
    ];
    sort($expected_config_items);
    $actual_config_items = $packages[self::PACKAGE_NAME]->getConfig();
    sort($actual_config_items);

    $this->assertEquals($expected_config_items, $actual_config_items, 'Expected configuration items not present in article package.');
  }

  /**
   * Enables a specified assignment method.
   *
   * @param string $method_id
   *   The ID of an assignment method.
   * @param bool $exclusive
   *   (optional) Whether to set the method as the only enabled method.
   *   Defaults to TRUE.
   */
  protected function enableAssignmentMethod($method_id, $exclusive = TRUE) {
    $settings = \Drupal::configFactory()->getEditable('features.settings');
    if ($exclusive) {
      $settings->set('assignment.enabled', [$method_id]);
    }
    else {
      $enabled = $settings->get('assignment.enabled');
      if (!in_array($method_id, $enabled)) {
        $enabled[] = $method_id;
      }
      $settings->set('assignment.enabled', $enabled);
    }
    $settings->save();
  }

  /**
   * Adds a configuration item.
   *
   * @param string $name
   *   The config name.
   * @param array $data
   *   The config data.
   * @param array $properties
   *   (optional) Additional properties set on the object.
   */
  protected function addConfigurationItem($name, array $data = [], array $properties = []) {
    $config_collection = $this->featuresManager->getConfigCollection();
    $config_collection[$name] = new ConfigurationItem($name, $data, $properties);
    $this->featuresManager->setConfigCollection($config_collection);
  }

}
