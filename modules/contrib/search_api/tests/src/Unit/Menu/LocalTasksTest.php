<?php

namespace Drupal\Tests\search_api\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests whether Search API's local tasks work correctly.
 *
 * @group search_api
 */
class LocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set the path of the module dynamically.
    $module_path = str_replace(\Drupal::root(), '', __DIR__);
    $module_path = str_replace('tests/src/Unit/Menu', '', $module_path);
    $module_path = trim($module_path, '/');

    $this->directoryList = array('search_api' => $module_path);
  }

  /**
   * Tests whether the server's local tasks are present at the given route.
   *
   * @param string $route
   *   The route to test.
   *
   * @dataProvider getPageRoutesServer
   */
  public function testLocalTasksServer($route) {
    $tasks = array(
      0 => array(
        'entity.search_api_server.canonical',
        'entity.search_api_server.edit_form',
      ),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   *
   * @return array[]
   *   An array containing arrays with the arguments for a
   *   testLocalTasksServer() call.
   */
  public function getPageRoutesServer() {
    return array(
      array('entity.search_api_server.canonical'),
      array('entity.search_api_server.edit_form'),
    );
  }

  /**
   * Tests whether the index's local tasks are present at the given route.
   *
   * @param string $route
   *   The route to test.
   *
   * @dataProvider getPageRoutesIndex
   */
  public function testLocalTasksIndex($route) {
    $tasks = array(
      0 => array(
        'entity.search_api_index.canonical',
        'entity.search_api_index.edit_form',
        'entity.search_api_index.fields',
        'entity.search_api_index.processors',
      ),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   *
   * @return array[]
   *   An array containing arrays with the arguments for a
   *   testLocalTasksIndex() call.
   */
  public function getPageRoutesIndex() {
    return array(
      array('entity.search_api_index.canonical'),
      array('entity.search_api_index.edit_form'),
      array('entity.search_api_index.fields'),
      array('entity.search_api_index.processors'),
    );
  }

}
