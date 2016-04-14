<?php

namespace Drupal\search_api\Tests;

use Drupal\Component\Utility\Html;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;
use Drupal\search_api_test_backend\Plugin\search_api\tracker\Test;

/**
 * Tests the overall functionality of the Search API framework and admin UI.
 *
 * @group search_api
 */
class IntegrationTest extends WebTestBase {

  /**
   * The ID of the search server used for this test.
   *
   * @var string
   */
  protected $serverId;

  /**
   * A storage instance for indexes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $indexStorage;

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'node',
    'search_api',
    'search_api_test_backend',
    'field_ui',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->indexStorage = \Drupal::entityTypeManager()->getStorage('search_api_index');
  }

  /**
   * Tests various operations via the Search API's admin UI.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    $this->createServer();
    $this->checkServerAvailability();
    $this->createIndex();
    $this->createIndexDuplicate();
    $this->editServer();
    $this->editIndex();
    $this->checkUserIndexCreation();
    $this->checkContentEntityTracking();

    $this->enableAllProcessors();
    $this->checkFieldLabels();

    $this->addFieldsToIndex();
    $this->checkDataTypesTable();
    $this->removeFieldsFromIndex();

    $this->configureFilter();
    $this->configureFilterPage();
    $this->checkProcessorChanges();
    $this->changeProcessorFieldBoost();

    $this->setReadOnly();
    $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();

    $this->deleteServer();
  }

  /**
   * Tests what happens when an index has an integer as id/label.
   *
   * This needs to be in a separate test because we want to test the content
   * tracking behavior as well as the fields / processors editing and adding
   * without messing with the other index. This test also makes sure that the
   * server also has an integer as id/label.
   */
  public function testIntegerIndex() {
    $this->drupalLogin($this->adminUser);
    $this->getTestServer(789, 456);

    $this->drupalCreateNode(array('type' => 'article'));
    $this->drupalCreateNode(array('type' => 'article'));

    $this->drupalGet('admin/config/search/search-api/add-index');

    $this->indexId = 123;
    $edit = array(
      'name' => $this->indexId,
      'id' => $this->indexId,
      'status' => 1,
      'description' => 'test Index:: 123~',
      'server' => 456,
      'datasources[]' => array('entity:node'),
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText($this->t('The index was successfully saved.'));
    $this->assertText($this->t('Successfully tracked @count items for this index.', array('@count' => 2)));
    $this->assertEqual(2, $this->countTrackedItems());

    $this->enableAllProcessors();
    $this->checkFieldLabels();

    $this->addFieldsToIndex();
    $this->removeFieldsFromIndex();

    $this->configureFilter();
    $this->configureFilterPage();
    $this->checkProcessorChanges();
    $this->changeProcessorFieldBoost();

    $this->setReadOnly();
    $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();
  }

  /**
   * Tests creating a search server via the UI.
   */
  protected function createServer($server_id = '_test_server') {
    $this->serverId = $server_id;
    $server_name = 'Search API &{}<>! Server';
    $server_description = 'A >server< used for testing &.';
    $settings_path = $this->urlGenerator->generateFromRoute('entity.search_api_server.add_form', array(), array('absolute' => TRUE));

    $this->drupalGet($settings_path);
    $this->assertResponse(200, 'Server add page exists');

    $edit = array(
      'name' => '',
      'status' => 1,
      'description' => 'A server used for testing.',
      'backend' => 'search_api_test_backend',
    );

    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));
    $this->assertText($this->t('@name field is required.', array('@name' => $this->t('Server name'))));

    $edit = array(
      'name' => $server_name,
      'status' => 1,
      'description' => $server_description,
      'backend' => 'search_api_test_backend',
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));
    $this->assertText($this->t('@name field is required.', array('@name' => $this->t('Machine-readable name'))));

    $edit = array(
      'name' => $server_name,
      'id' => $this->serverId,
      'status' => 1,
      'description' => $server_description,
      'backend' => 'search_api_test_backend',
    );

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $this->assertText($this->t('The server was successfully saved.'));
    $this->assertUrl('admin/config/search/search-api/server/' . $this->serverId, array(), 'Correct redirect to server page.');
    $this->assertHtmlEscaped($server_name);
    $this->assertHtmlEscaped($server_description);

    $this->drupalGet('admin/config/search/search-api');
    $this->assertHtmlEscaped($server_name);
    $this->assertHtmlEscaped($server_description);
  }

  /**
   * Tests creating a search index via the UI.
   */
  protected function createIndex() {
    $settings_path = $this->urlGenerator->generateFromRoute('entity.search_api_index.add_form', array(), array('absolute' => TRUE));
    $this->indexId = 'test_index';
    $index_description = 'An >index< used for &! tęsting.';
    $index_name = 'Search >API< test &!^* index';

    $this->drupalGet($settings_path);
    $this->assertResponse(200);
    $edit = array(
      'status' => 1,
      'description' => $index_description,
    );

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertText($this->t('@name field is required.', array('@name' => $this->t('Index name'))));
    $this->assertText($this->t('@name field is required.', array('@name' => $this->t('Machine-readable name'))));
    $this->assertText($this->t('@name field is required.', array('@name' => $this->t('Data sources'))));

    $edit = array(
      'name' => $index_name,
      'id' => $this->indexId,
      'status' => 1,
      'description' => $index_description,
      'server' => $this->serverId,
      'datasources[]' => array('entity:node'),
    );

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $this->assertText($this->t('The index was successfully saved.'));
    // @todo Make this work correctly.
    // $this->assertUrl($this->getIndexPath('fields/add'), array(), 'Correct redirect to index page.');
    $this->assertHtmlEscaped($index_name);

    $this->drupalGet($this->getIndexPath('edit'));
    $this->assertHtmlEscaped($index_name);

    $index = $this->getIndex(TRUE);

    if ($this->assertTrue($index, 'Index was correctly created.')) {
      $this->assertEqual($index->label(), $edit['name'], 'Name correctly inserted.');
      $this->assertEqual($index->id(), $edit['id'], 'Index ID correctly inserted.');
      $this->assertTrue($index->status(), 'Index status correctly inserted.');
      $this->assertEqual($index->getDescription(), $edit['description'], 'Index ID correctly inserted.');
      $this->assertEqual($index->getServerId(), $edit['server'], 'Index server ID correctly inserted.');
      $this->assertEqual($index->getDatasourceIds(), $edit['datasources[]'], 'Index datasource id correctly inserted.');
    }
    else {
      // Since none of the other tests would work, bail at this point.
      throw new SearchApiException();
    }

    // Test the "Save and edit" button.
    $index2_id = 'test_index2';
    $edit['id'] = $index2_id;
    unset($edit['server']);
    $this->drupalPostForm($settings_path, $edit, $this->t('Save and edit'));

    $this->assertText($this->t('The index was successfully saved.'));
    $this->indexStorage->resetCache(array($index2_id));
    $index = $this->indexStorage->load($index2_id);
    $this->assertUrl($index->toUrl('add-fields'), array(), 'Correct redirect to index fields page.');

    $this->drupalGet('admin/config/search/search-api');
    $this->assertHtmlEscaped($index_name);
    $this->assertHtmlEscaped($index_description);
  }

  /**
   * Tests creating a search index with an existing machine name.
   */
  protected function createIndexDuplicate() {
    $index_add_page = 'admin/config/search/search-api/add-index';
    $this->drupalGet($index_add_page);
    $this->indexId = 'test_index';

    $edit = array(
      'name' => $this->indexId,
      'id' => $this->indexId,
      'server' => $this->serverId,
      'datasources[]' => array('entity:node'),
    );

    // Try to submit an index with a duplicate machine name.
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name is already in use. It must be unique.'));

    // Try to submit an index with a duplicate machine name after form
    // rebuilding via datasource submit.
    $this->drupalPostForm(NULL, $edit, array('path' => $index_add_page, 'triggering_element' => array('datasources_configure' => t('Configure'))));
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name is already in use. It must be unique.'));

    // Try to submit an index with a duplicate machine name after form
    // rebuilding via datasource submit using AJAX.
    $this->drupalPostAjaxForm(NULL, $edit, array('datasources_configure' => t('Configure')));
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertText($this->t('The machine-readable name is already in use. It must be unique.'));
  }

  /**
   * Tests whether editing a server works correctly.
   */
  protected function editServer() {
    $path = 'admin/config/search/search-api/server/' . $this->serverId . '/edit';
    $this->drupalGet($path);

    // Check if it's possible to change the machine name.
    $elements = $this->xpath('//form[@id="search-api-server-edit-form"]/div[contains(@class, "form-item-id")]/input[@disabled]');
    $this->assertEqual(count($elements), 1, 'Machine name cannot be changed.');

    $tracked_items_before = $this->countTrackedItems();

    $edit = array(
      'name' => 'Test server',
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    /** @var $index \Drupal\search_api\IndexInterface */
    $index = $this->indexStorage->load($this->indexId);
    $remaining = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEqual(0, $remaining, 'Index was not scheduled for re-indexing when saving its server.');

    \Drupal::state()->set('search_api_test_backend.return.postUpdate', TRUE);
    $this->drupalPostForm($path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $remaining = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEqual($tracked_items, $remaining, 'Backend could trigger re-indexing upon save.');
    $this->assertEqual($tracked_items_before, $tracked_items, 'Items are still tracked after re-indexing was triggered.');
  }

  /**
   * Tests editing a search index via the UI.
   */
  protected function editIndex() {
    $tracked_items = $this->countTrackedItems();
    $edit_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($edit_path);

    // Check if it's possible to change the machine name.
    $elements = $this->xpath('//form[@id="search-api-index-edit-form"]/div[contains(@class, "form-item-id")]/input[@disabled]');
    $this->assertEqual(count($elements), 1, 'Machine name cannot be changed.');

    // Test the AJAX functionality for configuring the tracker.
    $edit = array('tracker' => 'search_api_test_backend');
    $this->drupalPostAjaxForm(NULL, $edit, 'tracker_configure');
    $edit['tracker_config[foo]'] = 'foobar';
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);

    // Verify that everything was changed correctly.
    $index = $this->getIndex(TRUE);
    $tracker = $index->getTrackerInstance();
    $this->assertTrue($tracker instanceof Test, 'Tracker was successfully switched.');
    $this->assertEqual($tracker->getConfiguration(), array('foo' => 'foobar'), 'Tracker config was successfully saved.');
    $this->assertEqual($this->countTrackedItems(), $tracked_items, 'Items are still correctly tracked.');

    // Revert back to the default tracker for the rest of the test.
    $edit = array('tracker' => 'default');
    $this->drupalPostForm($edit_path, $edit, $this->t('Save'));
    $this->assertResponse(200);
  }

  /**
   * Tests that an entity without bundles can be used as a data source.
   */
  protected function checkUserIndexCreation() {
    $edit = array(
      'name' => 'IndexName',
      'id' => 'user_index',
      'datasources[]' => 'entity:user',
    );

    $this->drupalPostForm('admin/config/search/search-api/add-index', $edit, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText($this->t('The index was successfully saved.'));
    $this->assertText($edit['name']);
  }

  /**
   * Tests the server availability.
   */
  protected function checkServerAvailability() {
    $this->drupalGet('admin/config/search/search-api/server/' . $this->serverId . '/edit');

    $this->drupalGet('admin/config/search/search-api');
    $this->assertResponse(200);
    $this->assertRaw($this->t('Enabled'));

    \Drupal::state()->set('search_api_test_backend.return.isAvailable', FALSE);
    $this->drupalGet('admin/config/search/search-api');
    $this->assertResponse(200);
    $this->assertRaw($this->t('Unavailable'));

    \Drupal::state()->set('search_api_test_backend.return.isAvailable', TRUE);
  }

  /**
   * Tests whether the tracking information is properly maintained.
   *
   * Will especially test the bundle option of the content entity datasource.
   */
  protected function checkContentEntityTracking() {
    // Initially there should be no tracked items, because there are no nodes.
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, 'No items are tracked yet.');

    // Add two articles and a page.
    $article1 = $this->drupalCreateNode(array('type' => 'article'));
    $this->drupalCreateNode(array('type' => 'article'));
    $this->drupalCreateNode(array('type' => 'page'));

    // Those 3 new nodes should be added to the tracking table immediately.
    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 3, 'Three items are tracked.');

    // Test disabling the index.
    $settings_path = $this->getIndexPath('edit');
    $this->drupalGet($settings_path);
    $edit = array(
      'status' => FALSE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => FALSE,
      'datasource_configs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, 'No items are tracked.');

    // Test re-enabling the index.
    $this->drupalGet($settings_path);

    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => TRUE,
      'datasource_configs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 3, 'Three items are tracked.');

    // Uncheck "default" and don't select any bundles. This should remove all
    // items from the tracking table.
    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => FALSE,
      'datasource_configs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 0, 'No items are tracked.');

    // Leave "default" unchecked and select the "article" bundle. This should
    // re-add the two articles to the tracking table.
    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => TRUE,
      'datasource_configs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 2, 'Two items are tracked.');

    // Leave "default" unchecked and select only the "page" bundle. This should
    // result in only the page being present in the tracking table.
    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => FALSE,
      'datasource_configs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, 'One item is tracked.');

    // Check "default" again and select the "article" bundle. This shouldn't
    // change the tracking table, which should still only contain the page.
    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 1,
      'datasource_configs[entity:node][bundles][article]' => TRUE,
      'datasource_configs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, 'One item is tracked.');

    // Leave "default" checked but now select only the "page" bundle. This
    // should result in only the articles being tracked.
    $edit = array(
      'status' => TRUE,
      'datasource_configs[entity:node][default]' => 1,
      'datasource_configs[entity:node][bundles][article]' => FALSE,
      'datasource_configs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 2, 'Two items are tracked.');

    // Delete an article. That should remove it from the item table.
    $article1->delete();

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, 1, 'One item is tracked.');
  }

  /**
   * Counts the number of tracked items in the test index.
   *
   * @return int
   *   The number of tracked items in the test index.
   */
  protected function countTrackedItems() {
    return $this->getIndex()->getTrackerInstance()->getTotalItemsCount();
  }

  /**
   * Counts the number of unindexed items in the test index.
   *
   * @return int
   *   The number of unindexed items in the test index.
   */
  protected function countRemainingItems() {
    return $this->getIndex()->getTrackerInstance()->getRemainingItemsCount();
  }

  /**
   * Enables all processors.
   */
  public function enableAllProcessors() {
    $this->drupalGet($this->getIndexPath('processors'));

    $edit = array(
      'status[aggregated_field]' => 1,
      'status[content_access]' => 1,
      'status[highlight]' => 1,
      'status[html_filter]' => 1,
      'status[ignorecase]' => 1,
      'status[ignore_character]' => 1,
      'status[node_status]' => 1,
      'status[rendered_item]' => 1,
      'status[stopwords]' => 1,
      'status[tokenizer]' => 1,
      'status[transliteration]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertText($this->t('The indexing workflow was successfully edited.'));
  }

  /**
   * Tests that field labels are always properly escaped.
   */
  protected function checkFieldLabels() {
    $permissions = array(
      'administer search_api',
      'access administration pages',
      'administer content types',
      'administer node fields',
    );
    $this->drupalLogin($this->createUser($permissions));

    $content_type_name = '&%@Content()_=';

    // Add a new content type with funky chars.
    $edit = array(
      'name' => $content_type_name,
      'type' => '_content_',
    );
    $this->drupalGet('admin/structure/types/add');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit, $this->t('Save and manage fields'));

    $field_name = '^6%{[*>.<"field';

    // Add a field to that content type with funky chars.
    $edit = array(
      'new_storage_type' => 'string',
      'label' => $field_name,
      'field_name' => '_field_',
    );
    $this->drupalGet('admin/structure/types/manage/_content_/fields/add-field');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, $edit, $this->t('Save and continue'));
    $this->drupalPostForm(NULL, array(), $this->t('Save field settings'));

    $url_options['query']['datasource'] = 'entity:node';
    $this->drupalGet($this->getIndexPath('fields/add'), $url_options);
    $this->assertHtmlEscaped($field_name);

    $this->addField('entity:node', 'field__field_', $field_name);

    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertHtmlEscaped($field_name);

    $edit = array(
      'datasource_configs[entity:node][default]' => 1,
    );
    $this->drupalGet($this->getIndexPath('edit'));
    $this->assertHtmlEscaped($content_type_name);
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));

    $this->drupalGet($this->getIndexPath('processors'));
    $this->assertHtmlEscaped($content_type_name);
    $this->assertHtmlEscaped($field_name);
  }

  /**
   * Tests whether adding fields to the index works correctly.
   */
  protected function addFieldsToIndex() {
    $fields = array(
      'nid' => $this->t('ID'),
      'title' => $this->t('Title'),
      'body' => $this->t('Body'),
      'revision_log' => $this->t('Revision log message'),
    );
    foreach ($fields as $property_path => $label) {
      $this->addField('entity:node', $property_path, $label);
    }

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();

    $this->assertTrue(!empty($fields['nid']), 'nid field is indexed.');

    // The "Content access" processor correctly marked fields as locked.
    if ($this->assertTrue(!empty($fields['uid']), 'uid field is indexed.')) {
      $this->assertTrue($fields['uid']->isIndexedLocked(), 'uid field is locked.');
      $this->assertTrue($fields['uid']->isTypeLocked(), 'uid field is type-locked.');
      $this->assertEqual($fields['uid']->getType(), 'integer', 'uid field has type integer.');
    }
    if ($this->assertTrue(!empty($fields['status']), 'status field is indexed.')) {
      $this->assertTrue($fields['status']->isIndexedLocked(), 'status field is locked.');
      $this->assertTrue($fields['status']->isTypeLocked(), 'status field is type-locked.');
      $this->assertEqual($fields['status']->getType(), 'boolean', 'status field has type boolean.');
    }

    // Check that a 'parent_data_type.data_type' Search API field type => data
    // type mapping relationship works.
    if ($this->assertTrue(!empty($fields['body']), 'body field is indexed.')) {
      $this->assertEqual($fields['body']->getType(), 'text', 'Complex field mapping relationship works.');
    }

    $edit = array(
      'fields[title][type]' => 'text',
      'fields[title][boost]' => '21.0',
      'fields[revision_log][type]' => 'search_api_test_data_type',
    );
    $this->drupalPostForm($this->getIndexPath('fields'), $edit, $this->t('Save changes'));
    $this->assertText($this->t('The changes were successfully saved.'));

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();

    if ($this->assertTrue(!empty($fields['title']), 'type field is indexed.')) {
      $this->assertEqual($fields['title']->getType(), $edit['fields[title][type]'], 'title field type is text.');
      $this->assertEqual($fields['title']->getBoost(), $edit['fields[title][boost]'], 'title field boost value is 21.');
    }
    if ($this->assertTrue(!empty($fields['revision_log']), 'revision_log field is indexed.')) {
      $this->assertEqual($fields['revision_log']->getType(), $edit['fields[revision_log][type]'], 'revision_log field type is search_api_test_data_type.');
    }
  }

  /**
   * Tests if the data types table is available and contains correct values.
   */
  protected function checkDataTypesTable() {
    $this->drupalGet($this->getIndexPath('fields'));
    $rows = $this->xpath('//*[@id="search-api-data-types-table"]/*/table/tbody/tr');
    $this->assertTrue(is_array($rows) && !empty($rows), 'Found a datatype listing.');

    /** @var \SimpleXMLElement $row */
    foreach ($rows as $row) {
      $label = (string) $row->td[0];
      $icon = basename($row->td[2]->img['src']);
      $fallback = (string) $row->td[3];

      // Make sure we display the right icon and fallback column.
      if (strpos($label, 'Unsupported') === 0) {
        $this->assertEqual($icon, 'error.svg', 'An error icon is shown for unsupported data types.');
        $this->assertNotEqual($fallback, '', 'The fallback data type label is not empty for unsupported data types.');
      }
      else {
        $this->assertEqual($icon, 'check.svg', 'A check icon is shown for supported data types.');
        $this->assertEqual($fallback, '', 'The fallback data type label is empty for supported data types.');
      }
    }
  }

  /**
   * Adds a field for a specific property to the index.
   *
   * @param string|null $datasource_id
   *   The property's datasource's ID, or NULL if it is a datasource-independent
   *   property.
   * @param string $property_path
   *   The property path.
   * @param string|null $label
   *   (optional) If given, the label to check for in the success message.
   */
  protected function addField($datasource_id, $property_path, $label = NULL) {
    $path = $this->getIndexPath('fields/add');
    $url_options = array('query' => array('datasource' => $datasource_id));
    if ($this->getUrl() === $this->buildUrl($path, $url_options)) {
      $path = NULL;
    }

    // Unfortunately it doesn't seem possible to specify the clicked button by
    // anything other than label, so we have to pass it as extra POST data.
    $combined_property_path = Utility::createCombinedId($datasource_id, $property_path);
    $post = '&' . $this->serializePostValues(array($combined_property_path => $this->t('Add')));
    $this->drupalPostForm($path, array(), NULL, $url_options, array(), NULL, $post);
    if ($label) {
      $args['%label'] = $label;
      $this->assertRaw($this->t('Field %label was added to the index.', $args));
    }
  }

  /**
   * Tests whether removing fields from the index works correctly.
   */
  protected function removeFieldsFromIndex() {
    // Find the "Remove" link for the "body" field.
    $links = $this->xpath('//a[@data-drupal-selector=:id]', array(':id' => 'edit-fields-body-remove'));
    if (empty($links)) {
      $this->fail('Found "Remove" link for body field');
    }
    else {
      $url_target = $this->getAbsoluteUrl($links[0]['href']);
      $this->pass('Found "Remove" link for body field');
      $this->drupalGet($url_target);
    }

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();
    $this->assertTrue(!isset($fields['body']), 'The body field has been removed from the index.');
  }

  /**
   * Tests that configuring a processor works.
   */
  protected function configureFilter() {
    $edit = array(
      'status[ignorecase]' => 1,
      'processors[ignorecase][settings][fields][search_api_language]' => FALSE,
      'processors[ignorecase][settings][fields][title]' => 'title',
    );
    $this->drupalPostForm($this->getIndexPath('processors'), $edit, $this->t('Save'));
    $index = $this->getIndex(TRUE);
    $processors = $index->getProcessors();
    if (isset($processors['ignorecase'])) {
      $configuration = $processors['ignorecase']->getConfiguration();
      $this->assertTrue(empty($configuration['fields']['search_api_language']), 'Language field disabled for ignore case filter.');
    }
    else {
      $this->fail('"Ignore case" processor not enabled.');
    }
    $this->assertText($this->t('The indexing workflow was successfully edited.'));
  }

  /**
   * Tests that the "no values changed" message on the "Processors" tab works.
   */
  public function configureFilterPage() {
    $edit = array();
    $this->drupalPostForm($this->getIndexPath('processors'), $edit, $this->t('Save'));
    $this->assertText('No values were changed.');
  }

  /**
   * Tests that changing or a processor doesn't always trigger reindexing.
   */
  protected function checkProcessorChanges() {
    $edit = array(
      'status[ignorecase]' => 1,
      'processors[ignorecase][settings][fields][search_api_language]' => FALSE,
      'processors[ignorecase][settings][fields][title]' => 'title',
    );
    // Enable just the ignore case processor, just to have a clean default state
    // before testing.
    $this->drupalPostForm($this->getIndexPath('processors'), $edit, $this->t('Save'));
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText($this->t('No values were changed.'));
    $this->assertNoText($this->t('All content was scheduled for reindexing so the new settings can take effect.'));

    $edit['processors[ignorecase][settings][fields][title]'] = FALSE;
    $this->drupalPostForm(NULL, $edit, $this->t('Save'));
    $this->assertResponse(200);
    $this->assertText($this->t('All content was scheduled for reindexing so the new settings can take effect.'));
  }

  /**
   * Tests that a field added by a processor can be changed.
   *
   * For most fields added by processors, such as the "URL field" processor,
   * only be the "Indexed" checkbox should be locked, not type and boost. This
   * method verifies this.
   */
  protected function changeProcessorFieldBoost() {
    // Add the URL field.
    $this->addField(NULL, 'search_api_url', $this->t('URI'));

    // Change the boost of the field.
    $this->drupalGet($this->getIndexPath('fields'));
    $this->drupalPostForm(NULL, array('fields[search_api_url][boost]' => '8.0'), $this->t('Save changes'));
    $this->assertText('The changes were successfully saved.');
    $this->assertOptionSelected('edit-fields-search-api-url-boost', '8.0', 'Boost is correctly saved.');

    // Change the type of the field.
    $this->drupalGet($this->getIndexPath('fields'));
    $this->drupalPostForm(NULL, array('fields[search_api_url][type]' => 'text'), $this->t('Save changes'));
    $this->assertText('The changes were successfully saved.');
    $this->assertOptionSelected('edit-fields-search-api-url-type', 'text', 'Type is correctly saved.');
  }

  /**
   * Sets an index to "read only" and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index is set to "read only", it
   * keeps tracking but won't index any items.
   */
  protected function setReadOnly() {
    $index = $this->getIndex(TRUE);
    $index->reindex();

    $index_path = $this->getIndexPath();
    $settings_path = $index_path . '/edit';

    // Re-enable tracking of all bundles. After this there should be two
    // unindexed items tracked by the index.
    $edit = array(
      'status' => TRUE,
      'read_only' => TRUE,
      'datasource_configs[entity:node][default]' => 0,
      'datasource_configs[entity:node][bundles][article]' => TRUE,
      'datasource_configs[entity:node][bundles][page]' => TRUE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $index = $this->getIndex(TRUE);
    $remaining_before = $this->countRemainingItems();

    $this->drupalGet($index_path);

    $this->assertNoText($this->t('Index now'), 'The "Index now" button is not displayed.');

    // Also try indexing via the API to make sure it is really not possible.
    $indexed = $index->indexItems();
    $this->assertEqual(0, $indexed, 'No items were indexed after setting the index to "read only".');
    $remaining_after = $this->countRemainingItems();
    $this->assertEqual($remaining_before, $remaining_after, 'No items were indexed after setting the index to "read only".');

    // Disable "read only" and verify indexing now works again.
    $edit = array(
      'read_only' => FALSE,
      'datasource_configs[entity:node][default]' => TRUE,
      'datasource_configs[entity:node][bundles][article]' => FALSE,
      'datasource_configs[entity:node][bundles][page]' => FALSE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $this->drupalPostForm($index_path, array(), $this->t('Index now'));

    $remaining_after = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEqual(0, $remaining_after, 'Items were indexed after removing the "read only" flag.');
  }

  /**
   * Disables and enables an index and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index is disabled, all its items
   * are removed from both the tracker and the server.
   *
   * When it is enabled again, the items are re-added to the tracker.
   */
  protected function disableEnableIndex() {
    // Disable the index and check that no items are tracked.
    $settings_path = $this->getIndexPath('edit');
    $edit = array(
      'status' => FALSE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual(0, $tracked_items, 'No items are tracked after disabling the index.');
    $tracked_items = \Drupal::database()->select('search_api_item', 'i')->countQuery()->execute()->fetchField();
    $this->assertEqual(0, $tracked_items, 'No items left in tracking table.');

    // @todo Also try to verify whether the items got deleted from the server.

    // Re-enable the index and check that the items are tracked again.
    $edit = array(
      'status' => TRUE,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual(2, $tracked_items, 'After enabling the index, 2 items are tracked.');
  }

  /**
   * Changes the index's datasources and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index's datasources are changed, the
   * tracker should remove all items from the datasources it no longer needs to
   * handle and add the new ones.
   */
  protected function changeIndexDatasource() {
    $index = $this->getIndex(TRUE);
    $index->reindex();

    $user_count = \Drupal::entityQuery('user')->count()->execute();
    $node_count = \Drupal::entityQuery('node')->count()->execute();

    // Enable indexing of users.
    $settings_path = $this->getIndexPath('edit');
    $edit = array(
      'datasources[]' => array('entity:user', 'entity:node'),
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, $user_count + $node_count, 'Correct number of items tracked after enabling the "User" datasource.');

    // Disable indexing of users again.
    $edit = array(
      'datasources[]' => array('entity:node'),
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    $tracked_items = $this->countTrackedItems();
    $this->assertEqual($tracked_items, $node_count, 'Correct number of items tracked after disabling the "User" datasource.');
  }

  /**
   * Changes the index's server and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index's server is changed, all of
   * the index's items should be removed from the previous server and marked as
   * "unindexed" in the tracker.
   */
  protected function changeIndexServer() {
    $index = $this->getIndex(TRUE);

    $node_count = \Drupal::entityQuery('node')->count()->execute();
    $this->assertEqual($node_count, $this->countTrackedItems(), 'All nodes are correctly tracked by the index.');

    // Index all remaining items on the index.
    $index->indexItems();

    $remaining_items = $this->countRemainingItems();
    $this->assertEqual($remaining_items, 0, 'All items have been successfully indexed.');

    // Create a second search server.
    $this->createServer('test_server_2');

    // Change the index's server to the new one.
    $settings_path = $this->getIndexPath('edit');
    $edit = array(
      'server' => $this->serverId,
    );
    $this->drupalPostForm($settings_path, $edit, $this->t('Save'));

    // After saving the new index, we should have called reindex.
    $remaining_items = $this->countRemainingItems();
    $this->assertEqual($remaining_items, $node_count, 'All items still need to be indexed.');
  }

  /**
   * Tests deleting a search server via the UI.
   */
  protected function deleteServer() {
    $server = Server::load($this->serverId);

    // Load confirmation form.
    $this->drupalGet('admin/config/search/search-api/server/' . $this->serverId . '/delete');
    $this->assertResponse(200, 'Server delete page exists');
    $this->assertRaw(t('Are you sure you want to delete the search server %name?', array('%name' => $server->label())), 'Deleting a server sks for confirmation.');
    $this->assertText(t('Deleting a server will disable all its indexes and their searches.'), 'Correct warning is displayed when deleting a server.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('The search server %name has been deleted.', array('%name' => $server->label())), 'The server was deleted.');
    $this->assertFalse(Server::load($this->serverId), 'Server could not be found anymore.');
    $this->assertUrl('admin/config/search/search-api', array(), 'Correct redirect to search api overview page.');

    // Confirm that the index hasn't been deleted.
    /** @var $index \Drupal\search_api\IndexInterface */
    $index = $this->indexStorage->load($this->indexId, TRUE);
    if ($this->assertTrue($index, 'The index associated with the server was not deleted.')) {
      $this->assertFalse($index->status(), 'The index associated with the server was disabled.');
      $this->assertNull($index->getServerId(), 'The index was removed from the server.');
    }
  }

  /**
   * Retrieves test index.
   *
   * @param bool $reset
   *   (optional) If TRUE, reset the entity cache before loading.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The test index.
   */
  protected function getIndex($reset = FALSE) {
    if ($reset) {
      $this->indexStorage->resetCache(array($this->indexId));
    }
    return $this->indexStorage->load($this->indexId);
  }

  /**
   * Ensures that all occurrences of the string are properly escaped.
   *
   * This makes sure that the string is only mentioned in an escaped version and
   * is never double escaped.
   *
   * @param string $string
   *   The raw string to check for.
   */
  protected function assertHtmlEscaped($string) {
    $this->assertRaw(Html::escape($string));
    $this->assertNoRaw(Html::escape(Html::escape($string)));
    $this->assertNoRaw($string);
  }

}
