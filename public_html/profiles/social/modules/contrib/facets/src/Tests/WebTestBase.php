<?php

namespace Drupal\facets\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\simpletest\WebTestBase as SimpletestWebTestBase;

/**
 * Provides the base class for web tests for Search API.
 */
abstract class WebTestBase extends SimpletestWebTestBase {

  use StringTranslationTrait;
  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'views',
    'node',
    'search_api',
    'search_api_test_backend',
    'facets',
    'block',
    'facets_search_api_dependency',
  ];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without Search / Facet admin permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
    ]);

    $this->unauthorizedUser = $this->drupalCreateUser(['access administration pages']);
    $this->anonymousUser = $this->drupalCreateUser();
  }

  /**
   * Creates or deletes a server.
   *
   * @param string $name
   *   (optional) The name of the server.
   * @param string $id
   *   (optional) The ID of the server.
   * @param string $backend_id
   *   (optional) The ID of the backend to set for the server.
   * @param array $backend_config
   *   (optional) The backend configuration to set for the server.
   * @param bool $reset
   *   (optional) If TRUE, delete the server instead of creating it. (Only the
   *   server's ID is required in that case).
   *
   * @return \Drupal\search_api\ServerInterface
   *   A search server.
   */
  public function getTestServer($name = 'WebTest server', $id = 'webtest_server', $backend_id = 'search_api_test_backend', $backend_config = array(), $reset = FALSE) {
    if ($reset) {
      $server = Server::load($id);
      if ($server) {
        $server->delete();
      }
    }
    else {
      $server = Server::create(array(
        'id' => $id,
        'name' => $name,
        'description' => $name,
        'backend' => $backend_id,
        'backend_config' => $backend_config,
      ));
      $server->save();
    }

    return $server;
  }

  /**
   * Creates or deletes an index.
   *
   * @param string $name
   *   (optional) The name of the index.
   * @param string $id
   *   (optional) The ID of the index.
   * @param string $server_id
   *   (optional) The server to which the index should be attached.
   * @param string $datasource_id
   *   (optional) The ID of a datasource to set for this index.
   * @param bool $reset
   *   (optional) If TRUE, delete the index instead of creating it. (Only the
   *   index's ID is required in that case).
   *
   * @return \Drupal\search_api\IndexInterface
   *   A search index.
   */
  public function getTestIndex($name = 'WebTest Index', $id = 'webtest_index', $server_id = 'webtest_server', $datasource_id = 'entity:node', $reset = FALSE) {
    if ($reset) {
      $index = Index::load($id);
      if ($index) {
        $index->delete();
      }
    }
    else {
      $index = Index::create(array(
        'id' => $id,
        'name' => $name,
        'description' => $name,
        'server' => $server_id,
        'datasources' => array($datasource_id),
      ));
      $index->save();
      $this->indexId = $index->id();
    }

    return $index;
  }

  /**
   * Retrieves the search index used by this test.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index.
   */
  protected function getIndex() {
    return Index::load($this->indexId);
  }

  /**
   * Clears the test index.
   */
  protected function clearIndex() {
    $this->getIndex()->clear();
  }

}
