<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility;

/**
 * Tests what happens when an index's dependencies are removed.
 *
 * @group search_api
 */
class DependencyRemovalTest extends KernelTestBase {

  /**
   * A search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * A config entity, to be used as a dependency in the tests.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $dependency;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'user',
    'system',
    'field',
    'search_api',
    'search_api_test_backend',
    'search_api_test_dependencies',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('system', 'key_value_expire');

    // The server tasks manager is needed when removing a server.
    $mock = $this->getMock('Drupal\search_api\Task\ServerTaskManagerInterface');
    $this->container->set('search_api.server_task_manager', $mock);

    // Create the index object, but don't save it yet since we want to change
    // its settings anyways in every test.
    $this->index = Index::create(array(
      'id' => 'test_index',
      'name' => 'Test index',
      'tracker_settings' => array(
        'default' => array(
          'plugin_id' => 'default',
          'settings' => array(),
        ),
      ),
      'datasource_settings' => array(
        'entity:user' => array(
          'plugin_id' => 'entity:user',
          'settings' => array(),
        ),
      ),
    ));

    // Use a search server as the dependency, since we have that available
    // anyways. The entity type should not matter at all, though.
    $this->dependency = Server::create(array(
      'id' => 'dependency',
      'name' => 'Test dependency',
      'backend' => 'search_api_test_backend',
    ));
    $this->dependency->save();
  }

  /**
   * Tests index with a field dependency that gets removed.
   */
  public function testFieldDependency() {
    // Add new field storage and field definitions.
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'field_search',
      'type' => 'string',
      'entity_type' => 'user',
    ));
    $field_storage->save();
    $field_search = FieldConfig::create(array(
      'field_name' => 'field_search',
      'field_type' => 'string',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Search Field',
    ));
    $field_search->save();

    // Create a Search API field/item and add it to the current index.
    $field = Utility::createFieldFromProperty($this->index, $field_storage->getPropertyDefinition('value'), 'entity:user', 'field_search', NULL, 'string');
    $field->setLabel('Search Field');
    $this->index->addField($field);
    $this->index->save();

    // New field has been added to the list of dependencies.
    $config_dependencies = \Drupal::config('search_api.index.' . $this->index->id())->get('dependencies.config');
    $this->assertContains($field_storage->getConfigDependencyName(), $config_dependencies);

    // Remove a dependent field.
    $field_storage->delete();

    // Index has not been deleted and index dependencies were updated.
    $this->reloadIndex();
    $dependencies = \Drupal::config('search_api.index.' . $this->index->id())->get('dependencies');
    $this->assertFalse(isset($dependencies['config'][$field_storage->getConfigDependencyName()]));
  }

  /**
   * Tests a backend with a dependency that gets removed.
   *
   * If the dependency does not get removed, proper cascading to the index is
   * also verified.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the backend when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testBackendDependency($remove_dependency) {
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();

    // Create a server using the test backend, and set the dependency in the
    // configuration.
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::create(array(
      'id' => 'test_server',
      'name' => 'Test server',
      'backend' => 'search_api_test_backend',
      'backend_config' => array(
        'dependencies' => array(
          $dependency_key => array(
            $dependency_name,
          ),
        ),
      ),
    ));
    $server->save();
    $server_dependency_key = $server->getConfigDependencyKey();
    $server_dependency_name = $server->getConfigDependencyName();

    // Set the server on the index and save that, too. However, we don't want
    // the index enabled, since that would lead to all kinds of overhead which
    // is completely irrelevant for this test.
    $this->index->setServer($server);
    $this->index->disable();
    $this->index->save();

    // Check that the dependencies were calculated correctly.
    $server_dependencies = $server->getDependencies();
    $this->assertContains($dependency_name, $server_dependencies[$dependency_key], 'Backend dependency correctly inserted');
    $index_dependencies = $this->index->getDependencies();
    $this->assertContains($server_dependency_name, $index_dependencies[$server_dependency_key], 'Server dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_backend\Plugin\search_api\backend\TestBackend::onDependencyRemoval().
    $key = 'search_api_test_backend.return.onDependencyRemoval';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the backend's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Reload the server.
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_server');
    $storage->resetCache();
    $server = $storage->load($server->id());

    if ($remove_dependency) {
      $this->assertInstanceOf('Drupal\search_api\ServerInterface', $server, 'Server was not removed');
      $this->assertArrayNotHasKey('dependencies', $server->get('backend_config'), 'Backend config was adapted');
      // @todo Logically, this should not be changed: if the server does not get
      //   removed, there is no need to adapt the index's configuration.
      //   However, the way this config dependency cascading is actually
      //   implemented in
      //   \Drupal\Core\Config\ConfigManager::getConfigEntitiesToChangeOnDependencyRemoval()
      //   does not seem to follow that logic, but just computes the complete
      //   tree of dependencies once and operates generally on the assumption
      //   that all of them will be deleted. See #2642374.
//      $this->assertEquals($server->id(), $this->index->getServerId(), "Index's server was not changed");
    }
    else {
      $this->assertNull($server, 'Server was removed');
      $this->assertEquals(NULL, $this->index->getServerId(), 'Index server was changed');
    }
  }

  /**
   * Tests a datasource with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the datasource when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testDatasourceDependency($remove_dependency) {
    // Add the datasource to the index and save it. The datasource configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();

    // Also index users, to verify that they are unaffected by the processor.
    $manager = \Drupal::getContainer()
      ->get('plugin.manager.search_api.datasource');
    $datasources['entity:user'] = $manager->createInstance('entity:user', array('index' => $this->index));
    $datasources['search_api_test_dependencies'] = $manager->createInstance(
      'search_api_test_dependencies',
      array(
        $dependency_key => array($dependency_name),
        'index' => $this->index,
      )
    );
    $this->index->setDatasources($datasources);

    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Datasource dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\datasource\TestDatasource::onDependencyRemoval().
    $key = 'search_api_test_dependencies.datasource.remove';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the datasource's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Datasource dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $datasources = $this->index->getDatasources();
    if ($remove_dependency) {
      $this->assertArrayHasKey('search_api_test_dependencies', $datasources, 'Datasource not removed');
      $this->assertEmpty($datasources['search_api_test_dependencies']->getConfiguration(), 'Datasource settings adapted');
    }
    else {
      $this->assertArrayNotHasKey('search_api_test_dependencies', $datasources, 'Datasource removed');
    }
  }

  /**
   * Tests removing the (hard) dependency of the index's single datasource.
   */
  public function testSingleDatasourceDependency() {
    // Add the datasource to the index and save it. The datasource configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();
    $datasources['search_api_test_dependencies'] = \Drupal::getContainer()
      ->get('plugin.manager.search_api.datasource')
      ->createInstance(
        'search_api_test_dependencies',
        array($dependency_key => array($dependency_name))
      );
    $this->index->setDatasources($datasources);

    $this->index->save();

    // Since in this test the index will be removed, we need a mock key/value
    // store (the index will purge any unsaved configuration of it upon
    // deletion, which uses a "user-shared temp store", which in turn uses a
    // key/value store).
    $mock = $this->getMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface');
    $mock_factory = $this->getMock('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface');
    $mock_factory->method('get')->willReturn($mock);
    $this->container->set('keyvalue.expirable', $mock_factory);

    // Delete the datasource's dependency.
    $this->dependency->delete();

    // Reload the index to ensure it was deleted.
    $this->reloadIndex();
    $this->assertNull($this->index, 'Index was removed');
  }

  /**
   * Tests a processor with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the processor when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testProcessorDependency($remove_dependency) {
    // Add the processor to the index and save it. The processor configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();

    $processor = \Drupal::getContainer()
      ->get('plugin.manager.search_api.processor')
      ->createInstance(
        'search_api_test_dependencies',
        array($dependency_key => array($dependency_name))
      );
    $this->index->addProcessor($processor);
    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Processor dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\processor\TestProcessor::onDependencyRemoval().
    $key = 'search_api_test_dependencies.processor.remove';
    \Drupal::state()->set($key, $remove_dependency);

    // Delete the processor's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Processor dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $processors = $this->index->getProcessors();
    if ($remove_dependency) {
      $this->assertArrayHasKey('search_api_test_dependencies', $processors, 'Processor not removed');
      $this->assertEmpty($processors['search_api_test_dependencies']->getConfiguration(), 'Processor settings adapted');
    }
    else {
      $this->assertArrayNotHasKey('search_api_test_dependencies', $processors, 'Processor removed');
    }
  }

  /**
   * Tests a tracker with a dependency that gets removed.
   *
   * @param bool $remove_dependency
   *   Whether to remove the dependency from the tracker when the object
   *   depended on is deleted.
   *
   * @dataProvider dependencyTestDataProvider
   */
  public function testTrackerDependency($remove_dependency) {
    // Set the tracker for the index and save it. The tracker configuration
    // contains the dependencies it will return – in our case, we use the test
    // server.
    $dependency_key = $this->dependency->getConfigDependencyKey();
    $dependency_name = $this->dependency->getConfigDependencyName();

    $tracker = \Drupal::getContainer()
      ->get('plugin.manager.search_api.tracker')
      ->createInstance('search_api_test_dependencies', array(
        $dependency_key => array(
          $dependency_name,
        ),
      ));
    $this->index->setTracker($tracker);
    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains($dependency_name, $dependencies[$dependency_key], 'Tracker dependency correctly inserted');

    // Set our magic state key to let the test plugin know whether the
    // dependency should be removed or not. See
    // \Drupal\search_api_test_dependencies\Plugin\search_api\tracker\TestTracker::onDependencyRemoval().
    $key = 'search_api_test_dependencies.tracker.remove';
    \Drupal::state()->set($key, $remove_dependency);
    // If the index resets the tracker, it needs to know the ID of the default
    // tracker.
    if (!$remove_dependency) {
      \Drupal::configFactory()->getEditable('search_api.settings')
        ->set('default_tracker', 'default')
        ->save();
    }

    // Delete the tracker's dependency.
    $this->dependency->delete();

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed, one way or the other.
    $dependencies = $this->index->getDependencies();
    $dependencies += array($dependency_key => array());
    $this->assertNotContains($dependency_name, $dependencies[$dependency_key], 'Tracker dependency removed from index');

    // Depending on whether the plugin should have removed the dependency or
    // not, make sure the right action was taken.
    $tracker_instance = $this->index->getTrackerInstance();
    $tracker_id = $tracker_instance->getPluginId();
    $tracker_config = $tracker_instance->getConfiguration();
    if ($remove_dependency) {
      $this->assertEquals('search_api_test_dependencies', $tracker_id, 'Tracker not reset');
      $this->assertEmpty($tracker_config, 'Tracker settings adapted');
    }
    else {
      $this->assertEquals('default', $tracker_id, 'Tracker was reset');
      $this->assertEmpty($tracker_config, 'Tracker settings were cleared');
    }
  }

  /**
   * Tests whether module dependencies are handled correctly.
   */
  public function testModuleDependency() {
    // Test with all types of plugins at once.
    $datasources['search_api_test_dependencies'] = \Drupal::getContainer()
      ->get('plugin.manager.search_api.datasource')
      ->createInstance('search_api_test_dependencies', array('index' => $this->index));
    $datasources['entity:user'] = \Drupal::getContainer()
      ->get('plugin.manager.search_api.datasource')
      ->createInstance('entity:user', array('index' => $this->index));
    $this->index->setDatasources($datasources);

    $processor = \Drupal::getContainer()
      ->get('plugin.manager.search_api.processor')
      ->createInstance('search_api_test_dependencies');
    $this->index->addProcessor($processor);

    $tracker = \Drupal::getContainer()
      ->get('plugin.manager.search_api.tracker')
      ->createInstance('search_api_test_dependencies');
    $this->index->setTracker($tracker);

    $this->index->save();

    // Check the dependencies were calculated correctly.
    $dependencies = $this->index->getDependencies();
    $this->assertContains('search_api_test_dependencies', $dependencies['module'], 'Module dependency correctly inserted');

    // When the index resets the tracker, it needs to know the ID of the default
    // tracker.
    \Drupal::configFactory()->getEditable('search_api.settings')
      ->set('default_tracker', 'default')
      ->save();

    // Disabling modules in Kernel tests normally doesn't trigger any kind of
    // reaction, just removes it from the list of modules (e.g., to avoid
    // calling of a hook). Therefore, we have to trigger that behavior
    // ourselves.
    \Drupal::getContainer()->get('config.manager')->uninstall('module', 'search_api_test_dependencies');

    // Reload the index and check it's still there.
    $this->reloadIndex();
    $this->assertInstanceOf('Drupal\search_api\IndexInterface', $this->index, 'Index not removed');

    // Make sure the dependency has been removed.
    $dependencies = $this->index->getDependencies();
    $dependencies += array('module' => array());
    $this->assertNotContains('search_api_test_dependencies', $dependencies['module'], 'Module dependency removed from index');

    // Make sure all the plugins have been removed.
    $this->assertNotContains('search_api_test_dependencies', $this->index->getDatasources(), 'Datasource was removed');
    $this->assertArrayNotHasKey('search_api_test_dependencies', $this->index->getProcessors(), 'Processor was removed');
    $this->assertEquals('default', $this->index->getTrackerId(), 'Tracker was reset');
  }

  /**
   * Data provider for this class's test methods.
   *
   * If $remove_dependency is TRUE, in Plugin::onDependencyRemoval() it clears
   * its configuration (and thus its dependency, in those test plugins) and
   * returns TRUE, which the index will take as "all OK, dependency removed" and
   * leave the plugin where it is, only with updated configuration.
   *
   * If $remove_dependency is FALSE, Plugin::onDependencyRemoval() will do
   * nothing and just return FALSE, the index says "oh, that plugin still has
   * that removed dependency, so I should better remove the plugin" and the
   * plugin gets removed.
   *
   * @return array
   *   An array of argument arrays for this class's test methods.
   */
  public function dependencyTestDataProvider() {
    return array(
      'Remove dependency' => array(TRUE),
      'Keep dependency' => array(FALSE),
    );
  }

  /**
   * Reloads the index with the latest copy from storage.
   */
  protected function reloadIndex() {
    $storage = \Drupal::entityTypeManager()->getStorage('search_api_index');
    $storage->resetCache();
    $this->index = $storage->load($this->index->id());
  }

}
