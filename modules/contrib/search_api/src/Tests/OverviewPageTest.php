<?php

namespace Drupal\search_api\Tests;

use Drupal\Component\Utility\Html;
use Drupal\search_api\ServerInterface;

/**
 * Tests the Search API overview page.
 *
 * @group search_api
 */
class OverviewPageTest extends WebTestBase {

  /**
   * The path of the overview page.
   *
   * @var string
   */
  protected $overviewPageUrl;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    $this->drupalLogin($this->adminUser);

    $this->overviewPageUrl = 'admin/config/search/search-api';
  }

  /**
   * Tests that the overview has the correct permissions set.
   */
  public function testOverviewPermissions() {
    $this->drupalGet('admin/config');
    $this->assertText('Search API', 'Search API menu link is displayed.');

    $this->drupalGet($this->overviewPageUrl);
    $this->assertResponse(200, 'Admin user can access the overview page.');

    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet($this->overviewPageUrl);
    $this->assertResponse(403, "User without permissions doesn't have access to the overview page.");
  }

  /**
   * Tests the creation of a server and an index.
   */
  public function testServerAndIndexCreation() {
    $server_name = 'WebTest Server';
    $index_name = 'WebTest Index';

    // Make sure the overview is empty.
    $this->drupalGet($this->overviewPageUrl);

    $this->assertNoText($server_name);
    $this->assertNoText($index_name);

    // Test whether a newly created server appears on the overview page.
    $server = $this->getTestServer($server_name);

    $this->drupalGet($this->overviewPageUrl);

    $this->assertText($server->label(), 'Server present on overview page.');
    $this->assertRaw($server->get('description'), 'Description is present');
    $this->assertFieldByXPath('//tr[contains(@class,"' . Html::cleanCssIdentifier($server->getEntityTypeId() . '-' . $server->id()) . '") and contains(@class, "search-api-list-enabled")]', NULL, 'Server is in proper table');

    // Test whether a newly created index appears on the overview page.
    $index = $this->getTestIndex($index_name);

    $this->drupalGet($this->overviewPageUrl);

    $this->assertText($index->label(), 'Index present on overview page.');
    $this->assertRaw($index->get('description'), 'Index description is present');
    $this->assertFieldByXPath('//tr[contains(@class,"' . Html::cleanCssIdentifier($index->getEntityTypeId() . '-' . $index->id()) . '") and contains(@class, "search-api-list-enabled")]', NULL, 'Index is in proper table');
  }

  /**
   * Tests enable/disable operations for servers and indexes through the UI.
   */
  public function testServerAndIndexStatusChanges() {
    $server = $this->getTestServer();
    $this->assertEntityStatusChange($server);

    $index = $this->getTestIndex();
    $this->assertEntityStatusChange($index);

    // Disable the server and test that both itself and the index have been
    // disabled.
    $server->setStatus(FALSE)->save();
    $this->drupalGet($this->overviewPageUrl);
    $server_class = Html::cleanCssIdentifier($server->getEntityTypeId() . '-' . $server->id());
    $index_class = Html::cleanCssIdentifier($index->getEntityTypeId() . '-' . $index->id());
    $this->assertFieldByXPath('//tr[contains(@class,"' . $server_class . '") and contains(@class, "search-api-list-disabled")]', NULL, 'The server has been disabled.');
    $this->assertFieldByXPath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-disabled")]', NULL, 'The index has been disabled.');

    // Test that an index can't be enabled if its server is disabled.
    // @todo A non-working "Enable" link should not be displayed at all.
    $this->clickLink('Enable', 1);
    $this->drupalGet($this->overviewPageUrl);
    $this->assertFieldByXPath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-disabled")]', NULL, 'The index could not be enabled.');

    // Enable the server and try again.
    $server->setStatus(TRUE)->save();
    $this->drupalGet($this->overviewPageUrl);

    // This time the server is enabled so the first 'enable' link belongs to the
    // index.
    $this->clickLink('Enable');
    $this->drupalGet($this->overviewPageUrl);
    $this->assertFieldByXPath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-enabled")]', NULL, 'The index has been enabled.');

    // Create a new index without a server assigned and test that it can't be
    // enabled. The overview UI is not very consistent at the moment, so test
    // using API functions for now.
    $index2 = $this->getTestIndex('WebTest Index 2', 'test_index_2', NULL);
    $this->assertFalse($index2->status(), 'The newly created index without a server is disabled by default.');

    $index2->setStatus(TRUE)->save();
    $this->assertFalse($index2->status(), 'The newly created index without a server cannot be enabled.');
  }

  /**
   * Asserts enable/disable operations for a search server or index.
   *
   * @param \Drupal\search_api\ServerInterface|\Drupal\search_api\IndexInterface $entity
   *   A search server or index.
   */
  protected function assertEntityStatusChange($entity) {
    $this->drupalGet($this->overviewPageUrl);
    $row_class = Html::cleanCssIdentifier($entity->getEntityTypeId() . '-' . $entity->id());
    $this->assertFieldByXPath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-enabled")]', NULL, 'The newly created entity is enabled by default.');

    // The first "Disable" link on the page belongs to our server, the second
    // one to our index.
    $this->clickLink('Disable', $entity instanceof ServerInterface ? 0 : 1);

    // Submit the confirmation form and test that the entity has been disabled.
    $this->drupalPostForm(NULL, array(), 'Disable');
    $this->assertFieldByXPath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-disabled")]', NULL, 'The entity has been disabled.');

    // Now enable the entity and verify that the operation succeeded.
    $this->clickLink('Enable');
    $this->drupalGet($this->overviewPageUrl);
    $this->assertFieldByXPath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-enabled")]', NULL, 'The entity has benn enabled.');
  }

  /**
   * Tests server operations in the overview page.
   */
  public function testOperations() {
    /** @var $server \Drupal\search_api\ServerInterface */
    $server = $this->getTestServer();

    $this->drupalGet($this->overviewPageUrl);
    $basic_url = $this->urlGenerator->generateFromRoute('entity.search_api_server.canonical', array('search_api_server' => $server->id()));
    $this->assertRaw('<a href="' . $basic_url . '/edit">Edit</a>', 'Edit operation presents');
    $this->assertRaw('<a href="' . $basic_url . '/disable">Disable</a>', 'Disable operation presents');
    $this->assertRaw('<a href="' . $basic_url . '/delete">Delete</a>', 'Delete operation presents');
    $this->assertNoRaw('<a href="' . $basic_url . '/enable">Enable</a>', 'Enable operation is not present');

    $server->setStatus(FALSE)->save();
    $this->drupalGet($this->overviewPageUrl);

    // Since \Drupal\Core\Access\CsrfTokenGenerator uses the current session ID,
    // we cannot verify the validity of the token from here.
    $this->assertRaw('<a href="' . $basic_url . '/enable?token=', 'Enable operation present');
    $this->assertNoRaw('<a href="' . $basic_url . '/disable">Disable</a>', 'Disable operation  is not present');
  }

}
