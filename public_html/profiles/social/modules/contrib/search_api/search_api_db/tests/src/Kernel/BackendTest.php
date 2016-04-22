<?php

namespace Drupal\Tests\search_api_db\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Utility;
use Drupal\search_api_db\Plugin\search_api\backend\Database as BackendDatabase;

/**
 * Tests index and search capabilities using the Database search backend.
 *
 * @group search_api
 */
class BackendTest extends KernelTestBase {

  use ExampleContentTrait;
  use StringTranslationTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'user',
    'system',
    'entity_test',
    'text',
  );

  /**
   * A search server ID.
   *
   * @var string
   */
  protected $serverId = 'database_search_server';

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

    $this->installSchema('search_api', array('search_api_item', 'search_api_task'));
    $this->installSchema('system', array('router'));
    $this->installSchema('user', array('users_data'));
    $this->installEntitySchema('entity_test');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    \Drupal::state()->set('search_api_use_tracking_batch', FALSE);

    $this->setUpExampleStructure();

    $this->installConfig(array('search_api_test_db'));
  }

  /**
   * Tests various indexing scenarios for the Database search backend.
   *
   * Uses a single method to save time.
   */
  public function testFramework() {
    $this->insertExampleContent();
    $this->checkDefaultServer();
    $this->checkServerTables();
    $this->checkDefaultIndex();
    $this->updateIndex();
    $this->searchNoResults();
    $this->indexItems($this->indexId);
    $this->checkMultiValuedInfo();
    $this->searchSuccess1();
    $this->checkFacets();
    $this->regressionTests();

    $this->editServerPartial();
    $this->searchSuccessPartial();

    $this->editServer();
    $this->searchSuccess2();
    $this->clearIndex();

    $this->enableHtmlFilter();
    $this->indexItems($this->indexId);
    $this->disableHtmlFilter();
    $this->clearIndex();

    $this->searchNoResults();
    $this->regressionTests2();
    $this->checkModuleUninstall();
  }

  /**
   * Tests the server that was installed through default configuration files.
   */
  protected function checkDefaultServer() {
    $server = $this->getServer();
    $this->assertTrue((bool) $server, 'The server was successfully created.');
  }

  /**
   * Tests that all tables and all columns have been created.
   */
  protected function checkServerTables() {
    $db_info = $this->getIndexDbInfo();
    $normalized_storage_table = $db_info['index_table'];
    $field_tables = $db_info['field_tables'];

    $expected_fields = array(
      'body',
      'category',
      'id',
      'keywords',
      'name',
      'search_api_language',
      'type',
      'width',
    );
    $actual_fields = array_keys($field_tables);
    sort($actual_fields);
    sort($expected_fields);
    $this->assertEquals($expected_fields, $actual_fields, 'All expected field tables were created.');

    $this->assertTrue(\Drupal::database()->schema()->tableExists($normalized_storage_table), 'Normalized storage table exists');
    foreach ($field_tables as $field_table) {
      $this->assertTrue(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
      $this->assertTrue(\Drupal::database()->schema()->fieldExists($normalized_storage_table, $field_table['column']), new FormattableMarkup('Field column %column exists', array('%column' => $field_table['column'])));
    }
  }

  /**
   * Tests the index that was installed through default configuration files.
   */
  protected function checkDefaultIndex() {
    $index = $this->getIndex();
    $this->assertTrue((bool) $index, 'The index was successfully created.');

    $this->assertEquals(array("entity:entity_test"), $index->getDatasourceIds(), 'Datasources are set correctly.');
    $this->assertEquals('default', $index->getTrackerId(), 'Tracker is set correctly.');

    $this->assertEquals(5, $index->getTrackerInstance()->getTotalItemsCount(), 'Correct item count.');
    $this->assertEquals(0, $index->getTrackerInstance()->getIndexedItemsCount(), 'All items still need to be indexed.');
  }

  /**
   * Checks whether changes to the index's fields are picked up by the server.
   */
  protected function updateIndex() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();

    // Remove a field from the index and check if the change is matched in the
    // server configuration.
    $field = $index->getField('keywords');
    if (!$field) {
      throw new \Exception();
    }
    $index->removeField('keywords');
    $index->save();

    $index_fields = array_keys($index->getFields());

    $db_info = $this->getIndexDbInfo();
    $server_fields = array_keys($db_info['field_tables']);

    sort($index_fields);
    sort($server_fields);
    $this->assertEquals($index_fields, $server_fields);

    // Add the field back for the next assertions.
    $index->addField($field)->save();
  }

  /**
   * Enables the "HTML Filter" processor for the index.
   */
  protected function enableHtmlFilter() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();

    $processor = \Drupal::getContainer()
      ->get('plugin.manager.search_api.processor')
      ->createInstance('html_filter');

    $index->addProcessor($processor);
    $index->save();

    $this->assertArrayHasKey('html_filter', $index->getProcessors(), 'HTML filter processor is added.');
  }

  /**
   * Disables the "HTML Filter" processor for the index.
   */
  protected function disableHtmlFilter() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();
    $index->removeField('body');
    $index->removeProcessor('html_filter');
    $index->save();

    $this->assertArrayNotHasKey('html_filter', $index->getProcessors(), 'HTML filter processor is removed.');
    $this->assertArrayNotHasKey('body', $index->getFields(), 'Body field is removed.');
  }

  /**
   * Builds a search query for testing purposes.
   *
   * Used as a helper method during testing.
   *
   * @param string|array|null $keys
   *   (optional) The search keys to set, if any.
   * @param string[] $conditions
   *   (optional) Conditions to set on the query, in the format "field,value".
   * @param string[]|null $fields
   *   (optional) Fulltext fields to search for the keys.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A search query on the test index.
   */
  protected function buildSearch($keys = NULL, array $conditions = array(), array $fields = NULL) {
    $query = $this->getIndex()->query();
    if ($keys) {
      $query->keys($keys);
      if ($fields) {
        $query->setFulltextFields($fields);
      }
    }
    foreach ($conditions as $condition) {
      list($field, $value) = explode(',', $condition, 2);
      $query->addCondition($field, $value);
    }
    $query->range(0, 10);

    return $query;
  }

  /**
   * Tests that a search on the index doesn't have any results.
   */
  protected function searchNoResults() {
    $results = $this->buildSearch('test')->execute();
    $this->assertEquals(0, $results->getResultCount(), 'No search results returned without indexing.');
    $this->assertEquals(array(), array_keys($results->getResultItems()), 'No search results returned without indexing.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Verifies that the stored information about multi-valued fields is correct.
   */
  protected function checkMultiValuedInfo() {
    $db_info = $this->getIndexDbInfo();
    $field_info = $db_info['field_tables'];

    $fields = array('name', 'body', 'type', 'keywords', 'category', 'width');
    $multi_valued = array('name', 'body', 'keywords');
    foreach ($fields as $field_id) {
      $this->assertArrayHasKey($field_id, $field_info, "Field info saved for field $field_id.");
      if (in_array($field_id, $multi_valued)) {
        $this->assertFalse(empty($field_info[$field_id]['multi-valued']), "Field $field_id is stored as multi-value.");
      }
      else {
        $this->assertTrue(empty($field_info[$field_id]['multi-valued']), "Field $field_id is not stored as multi-value.");
      }
    }
  }

  /**
   * Tests whether some test searches have the correct results.
   */
  protected function searchSuccess1() {
    $results = $this->buildSearch('test')->range(1, 2)->sort('id', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search for »test« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 3)), array_keys($results->getResultItems()), 'Search for »test« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $ids = $this->getItemIds(array(2));
    $id = reset($ids);
    $this->assertEquals($id, key($results->getResultItems()));
    $this->assertEquals($id, $results->getResultItems()[$id]->getId());
    $this->assertEquals('entity:entity_test', $results->getResultItems()[$id]->getDatasourceId());

    $results = $this->buildSearch('test foo')->sort('id', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Search for »test foo« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4)), array_keys($results->getResultItems()), 'Search for »test foo« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo', array('type,item'))->sort('id', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Search for »foo« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2)), array_keys($results->getResultItems()), 'Search for »foo« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Complex search 1 returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(4)), array_keys($results->getResultItems()), 'Complex search 1 returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch()->sort('id');
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'bar');
    $conditions->addCondition('body', 'bar');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search with multi-field fulltext filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3, 5)), array_keys($results->getResultItems()), 'Search with multi-field fulltext filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', array('grape', 'apple'), 'IN')->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Query with IN filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 4, 5)), array_keys($results->getResultItems()), 'Query with IN filter field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', array('grape', 'apple'), 'NOT IN')->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Query with NOT IN filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 3)), array_keys($results->getResultItems()), 'Query with NOT IN filter field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('width', array('0.9', '1.5'), 'BETWEEN')->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Query with BETWEEN filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(4)), array_keys($results->getResultItems()), 'Query with BETWEEN filter field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Tests whether facets work correctly.
   */
  protected function checkFacets() {
    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR', array('facet:' . 'category'));
    $conditions->addCondition('category', 'article_category');
    $query->addConditionGroup($conditions);
    $facets['category'] = array(
      'field' => 'category',
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
      'operator' => 'or',
    );
    $query->setOption('search_api_facets', $facets);
    $results = $query->execute();
    $this->assertEquals(2, $results->getResultCount(), 'OR facets query returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(4, 5)), array_keys($results->getResultItems()));
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
      array('count' => 1, 'filter' => '!'),
    );
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $category_facets, 'Correct OR facets were returned');

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR', array('facet:' . 'category'));
    $conditions->addCondition('category', 'article_category');
    $query->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition('category', NULL, '<>');
    $query->addConditionGroup($conditions);
    $facets['category'] = array(
      'field' => 'category',
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
      'operator' => 'or',
    );
    $query->setOption('search_api_facets', $facets);
    $results = $query->execute();
    $this->assertEquals(2, $results->getResultCount(), 'OR facets query returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(4, 5)), array_keys($results->getResultItems()));
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
    );
    $category_facets = $results->getExtraData('search_api_facets')['category'];
    usort($category_facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $category_facets, 'Correct OR facets were returned');
  }

  /**
   * Edits the server to enable partial matches.
   *
   * @param bool $enable
   *   (optional) Whether partial matching should be enabled or disabled.
   */
  protected function editServerPartial($enable = TRUE) {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['partial_matches'] = $enable;
    $server->setBackendConfig($backend_config);
    $this->assertTrue((bool) $server->save(), 'The server was successfully edited.');
    $this->resetEntityCache();
  }

  /**
   * Tests whether partial searches work.
   */
  protected function searchSuccessPartial() {
    $results = $this->buildSearch('foobaz')->range(0, 1)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Partial search for »foobaz« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1)), array_keys($results->getResultItems()), 'Partial search for »foobaz« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo')
      ->sort('search_api_relevance', QueryInterface::SORT_DESC)
      ->sort('id')
      ->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Partial search for »foo« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 3, 5)), array_keys($results->getResultItems()), 'Partial search for »foo« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo tes')->sort('id')->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Partial search for »foo tes« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3, 4)), array_keys($results->getResultItems()), 'Partial search for »foo tes« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('oob est')->sort('id')->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Partial search for »oob est« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3)), array_keys($results->getResultItems()), 'Partial search for »oob est« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo nonexistent')->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Partial search for »foo nonexistent« returned correct number of results.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('bar nonexistent')->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Partial search for »foo nonexistent« returned correct number of results.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'oob',
      array(
        '#conjunction' => 'OR',
        'est',
        'nonexistent',
      ),
    );
    $results = $this->buildSearch($keys)->sort('id')->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Partial search for complex keys returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3)), array_keys($results->getResultItems()), 'Partial search for complex keys returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo', array('category,item_category'))
      ->sort('id', QueryInterface::SORT_DESC)
      ->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Partial search for »foo« with additional filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 1)), array_keys($results->getResultItems()), 'Partial search for »foo« with additional filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch()->sort('id');
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'test');
    $conditions->addCondition('body', 'test');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Partial search with multi-field fulltext filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3, 4)), array_keys($results->getResultItems()), 'Partial search with multi-field fulltext filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Edits the server to change the "Minimum word length" setting.
   */
  protected function editServer() {
    $server = $this->getServer();
    $backend_config = $server->getBackendConfig();
    $backend_config['min_chars'] = 4;
    $backend_config['partial_matches'] = FALSE;
    $server->setBackendConfig($backend_config);
    $success = (bool) $server->save();
    $this->assertTrue($success, 'The server was successfully edited.');

    $this->clearIndex();
    $this->indexItems($this->indexId);

    $this->resetEntityCache();
  }

  /**
   * Tests the results of some test searches with minimum word length of 4.
   */
  protected function searchSuccess2() {
    $results = $this->buildSearch('test')->range(1, 2)->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search for »test« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(4, 1)), array_keys($results->getResultItems()), 'Search for »test« returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch()->sort('id');
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('name', 'test');
    $conditions->addCondition('body', 'test');
    $query->addConditionGroup($conditions);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search with multi-field fulltext filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3, 4)), array_keys($results->getResultItems()), 'Search with multi-field fulltext filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch(NULL, array('body,test foobar'))->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Search with multi-term fulltext filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'Search with multi-term fulltext filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('test foo')->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Search for »test foo« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 4, 1, 3)), array_keys($results->getResultItems()), 'Search for »test foo« returned correct result.');
    $this->assertIgnored($results, array('foo'), 'Short key was ignored.');
    $this->assertWarnings($results);

    $results = $this->buildSearch('foo', array('type,item'))->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Search for »foo« returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3)), array_keys($results->getResultItems()), 'Search for »foo« returned correct result.');
    $this->assertIgnored($results, array('foo'), 'Short key was ignored.');
    $this->assertWarnings($results, array((string) $this->t('No valid search keys were present in the query.')), '"No valid keys" warning was displayed.');

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Complex search 1 returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'Complex search 1 returned correct result.');
    $this->assertIgnored($results, array('baz', 'bar'), 'Correct keys were ignored.');
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'OR',
        'baz',
        'foobar',
      ),
      array(
        '#conjunction' => 'OR',
        '#negation' => TRUE,
        'bar',
        'fooblob',
      ),
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Complex search 2 returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'Complex search 2 returned correct result.');
    $this->assertIgnored($results, array('baz', 'bar'), 'Correct keys were ignored.');
    $this->assertWarnings($results);

    $results = $this->buildSearch(NULL, array('keywords,orange'))->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Filter query 1 on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 5)), array_keys($results->getResultItems()), 'Filter query 1 on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $conditions = array(
      'keywords,orange',
      'keywords,apple',
    );
    $results = $this->buildSearch(NULL, $conditions)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Filter query 2 on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2)), array_keys($results->getResultItems()), 'Filter query 2 on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', 'orange', '<>')->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Negated filter on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3, 4)), array_keys($results->getResultItems()), 'Negated filter on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', NULL)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Query with NULL filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'Query with NULL filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', NULL, '<>')->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Query with NOT NULL filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 5)), array_keys($results->getResultItems()), 'Query with NOT NULL filter returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);
  }

  /**
   * Executes regression tests for issues that were already fixed.
   */
  protected function regressionTests() {
    /** @var \Drupal\search_api\ServerInterface $second_server */
    $second_server = Server::create(array(
      'id' => 'test2',
      'backend' => 'search_api_db',
      'backend_config' => array(
        'database' => 'default:default',
      ),
    ));
    $second_server->save();
    $query = $this->buildSearch();
    try {
      $second_server->search($query);
      $this->fail('Could execute a query for an index on a different server.');
    }
    catch (SearchApiException $e) {
      $this->assertTrue(TRUE, 'Executing a query for an index on a different server throws an exception.');
    }
    $second_server->delete();

    // Regression tests for #2007872.
    $results = $this->buildSearch('test')->sort('id', QueryInterface::SORT_ASC)->sort('type', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Sorting on field with NULLs returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 3, 4)), array_keys($results->getResultItems()), 'Sorting on field with NULLs returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('id', 3);
    $conditions->addCondition('type', 'article');
    $query->addConditionGroup($conditions);
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(3, $results->getResultCount(), 'OR filter on field with NULLs returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3, 4, 5)), array_keys($results->getResultItems()), 'OR filter on field with NULLs returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #1863672.
    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('keywords', 'orange');
    $conditions->addCondition('keywords', 'apple');
    $query->addConditionGroup($conditions);
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'OR filter on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 5)), array_keys($results->getResultItems()), 'OR filter on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('keywords', 'orange');
    $conditions->addCondition('keywords', 'strawberry');
    $query->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('OR');
    $conditions->addCondition('keywords', 'apple');
    $conditions->addCondition('keywords', 'grape');
    $query->addConditionGroup($conditions);
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Multiple OR filters on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 4, 5)), array_keys($results->getResultItems()), 'Multiple OR filters on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch();
    $conditions1 = $query->createConditionGroup('OR');
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition('keywords', 'orange');
    $conditions->addCondition('keywords', 'apple');
    $conditions1->addConditionGroup($conditions);
    $conditions = $query->createConditionGroup('AND');
    $conditions->addCondition('keywords', 'strawberry');
    $conditions->addCondition('keywords', 'grape');
    $conditions1->addConditionGroup($conditions);
    $query->addConditionGroup($conditions1);
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(3, $results->getResultCount(), 'Complex nested filters on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(2, 4, 5)), array_keys($results->getResultItems()), 'Complex nested filters on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2040543.
    $query = $this->buildSearch();
    $facets['category'] = array(
      'field' => 'category',
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
      array('count' => 1, 'filter' => '!'),
    );
    $type_facets = $results->getExtraData('search_api_facets')['category'];
    usort($type_facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $type_facets, 'Correct facets were returned');

    $query = $this->buildSearch();
    $facets['category']['missing'] = FALSE;
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article_category"'),
      array('count' => 2, 'filter' => '"item_category"'),
    );
    $type_facets = $results->getExtraData('search_api_facets')['category'];
    usort($type_facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $type_facets, 'Correct facets were returned');

    // Regression tests for #2111753.
    $keys = array(
      '#conjunction' => 'OR',
      'foo',
      'test',
    );
    $query = $this->buildSearch($keys, array(), array('name'));
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(3, $results->getResultCount(), 'OR keywords returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4)), array_keys($results->getResultItems()), 'OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch($keys, array(), array('name', 'body'));
    $query->range(0, 0);
    $results = $query->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Multi-field OR keywords returned correct number of results.');
    $this->assertFalse($results->getResultItems(), 'Multi-field OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      'foo',
      'test',
      array(
        '#conjunction' => 'AND',
        'bar',
        'baz',
      ),
    );
    $query = $this->buildSearch($keys, array(), array('name'));
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Nested OR keywords returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 5)), array_keys($results->getResultItems()), 'Nested OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      array(
        '#conjunction' => 'AND',
        'foo',
        'test',
      ),
      array(
        '#conjunction' => 'AND',
        'bar',
        'baz',
      ),
    );
    $query = $this->buildSearch($keys, array(), array('name', 'body'));
    $query->sort('id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'Nested multi-field OR keywords returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 5)), array_keys($results->getResultItems()), 'Nested multi-field OR keywords returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2127001.
    $keys = array(
      '#conjunction' => 'AND',
      '#negation' => TRUE,
      'foo',
      'bar',
    );
    $results = $this->buildSearch($keys)->sort('search_api_id', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Negated AND fulltext search returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3, 4)), array_keys($results->getResultItems()), 'Negated AND fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'OR',
      '#negation' => TRUE,
      'foo',
      'baz',
    );
    $results = $this->buildSearch($keys)->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Negated OR fulltext search returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'Negated OR fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $keys = array(
      '#conjunction' => 'AND',
      'test',
      array(
        '#conjunction' => 'AND',
        '#negation' => TRUE,
        'foo',
        'bar',
      ),
    );
    $results = $this->buildSearch($keys)->sort('search_api_id', QueryInterface::SORT_ASC)->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Nested NOT AND fulltext search returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3, 4)), array_keys($results->getResultItems()), 'Nested NOT AND fulltext search returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2136409.
    $query = $this->buildSearch();
    $query->addCondition('category', NULL);
    $query->sort('search_api_id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(1, $results->getResultCount(), 'NULL filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(3)), array_keys($results->getResultItems()), 'NULL filter returned correct result.');

    $query = $this->buildSearch();
    $query->addCondition('category', NULL, '<>');
    $query->sort('search_api_id', QueryInterface::SORT_ASC);
    $results = $query->execute();
    $this->assertEquals(4, $results->getResultCount(), 'NOT NULL filter returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 2, 4, 5)), array_keys($results->getResultItems()), 'NOT NULL filter returned correct result.');

    // Regression tests for #1658964.
    $query = $this->buildSearch();
    $facets['type'] = array(
      'field' => 'type',
      'limit' => 0,
      'min_count' => 0,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->addCondition('type', 'article');
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"article"'),
      array('count' => 0, 'filter' => '!'),
      array('count' => 0, 'filter' => '"item"'),
    );
    $facets = $results->getExtraData('search_api_facets', array())['type'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $facets, 'Correct facets were returned');

    // Regression tests for #2469547.
    $query = $this->buildSearch();
    $facets = array();
    $facets['body'] = array(
      'field' => 'body',
      'limit' => 0,
      'min_count' => 1,
      'missing' => FALSE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->addCondition('id', 5, '<>');
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 4, 'filter' => '"test"'),
      array('count' => 2, 'filter' => '"Case"'),
      array('count' => 2, 'filter' => '"casE"'),
      array('count' => 1, 'filter' => '"bar"'),
      array('count' => 1, 'filter' => '"case"'),
      array('count' => 1, 'filter' => '"foobar"'),
    );
    // We can't guarantee the order of returned facets, since "bar" and "foobar"
    // both occur once, so we have to manually sort the returned facets first.
    $facets = $results->getExtraData('search_api_facets', array())['body'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $facets, 'Correct facets were returned for a fulltext field.');

    // Regression tests for #1403916.
    $query = $this->buildSearch('test foo');
    $facets = array();
    $facets['type'] = array(
      'field' => 'type',
      'limit' => 0,
      'min_count' => 1,
      'missing' => TRUE,
    );
    $query->setOption('search_api_facets', $facets);
    $query->range(0, 0);
    $results = $query->execute();
    $expected = array(
      array('count' => 2, 'filter' => '"item"'),
      array('count' => 1, 'filter' => '"article"'),
    );
    $facets = $results->getExtraData('search_api_facets', array())['type'];
    usort($facets, array($this, 'facetCompare'));
    $this->assertEquals($expected, $facets, 'Correct facets were returned');

    // Regression tests for #2557291.
    $results = $this->buildSearch('case')->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Search for lowercase "case" returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1)), array_keys($results->getResultItems()), 'Search for lowercase "case" returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('Case')->sort('search_api_id')->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Search for capitalized "Case" returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 3)), array_keys($results->getResultItems()), 'Search for capitalized "Case" returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('CASE')->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Search for non-existent uppercase version of "CASE" returned correct number of results.');
    $this->assertEquals(array(), array_keys($results->getResultItems()), 'Search for non-existent uppercase version of "CASE" returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('föö')->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Search for keywords with umlauts returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1)), array_keys($results->getResultItems()), 'Search for keywords with umlauts returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch('smile' . json_decode('"\u1F601"'))->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Search for keywords with umlauts returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1)), array_keys($results->getResultItems()), 'Search for keywords with umlauts returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $results = $this->buildSearch()->addCondition('keywords', 'grape', '<>')->execute();
    $this->assertEquals(2, $results->getResultCount(), 'Negated filter on multi-valued field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(1, 3)), array_keys($results->getResultItems()), 'Negated filter on multi-valued field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression tests for #2511860.
    $query = $this->buildSearch();
    $query->addCondition('body', 'ab xy');
    $results = $query->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Fulltext filters on short words do not change the result.');

    $query = $this->buildSearch();
    $query->addCondition('body', 'ab ab');
    $results = $query->execute();
    $this->assertEquals(5, $results->getResultCount(), 'Fulltext filters on duplicate short words do not change the result.');
  }

  /**
   * Compares two facet filters to determine their order.
   *
   * Used as a callback for usort() in regressionTests().
   *
   * Will first compare the counts, ranking facets with higher count first, and
   * then by filter value.
   *
   * @param array $a
   *   The first facet filter.
   * @param array $b
   *   The second facet filter.
   *
   * @return int
   *   -1 or 1 if the first filter should, respectively, come before or after
   *   the second; 0 if both facet filters are equal.
   */
  protected function facetCompare(array $a, array $b) {
    if ($a['count'] != $b['count']) {
      return $b['count'] - $a['count'];
    }
    return strcmp($a['filter'], $b['filter']);
  }

  /**
   * Clears the test index.
   */
  protected function clearIndex() {
    $this->getIndex()->clear();
  }

  /**
   * Executes regression tests which are unpractical to run in between.
   */
  protected function regressionTests2() {
    // Create a "prices" field on the test entity type.
    FieldStorageConfig::create(array(
      'field_name' => 'prices',
      'entity_type' => 'entity_test',
      'type' => 'decimal',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    FieldConfig::create(array(
      'field_name' => 'prices',
      'entity_type' => 'entity_test',
      'bundle' => 'item',
      'label' => 'Prices',
    ))->save();

    // Regression test for #1916474.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->getIndex();
    $this->addField($index, 'prices', 'decimal');
    $success = $index->save();
    $this->assertTrue($success, 'The index field settings were successfully changed.');

    // Reset the static cache so the new values will be available.
    $this->resetEntityCache('server');
    $this->resetEntityCache();

    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 6,
        'prices' => array('3.5', '3.25', '3.75', '3.5'),
        'type' => 'item',
      ))->save();

    $this->indexItems($this->indexId);

    $query = $this->buildSearch(NULL, array('prices,3.25'));
    $results = $query->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Filter on decimal field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(6)), array_keys($results->getResultItems()), 'Filter on decimal field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $query = $this->buildSearch(NULL, array('prices,3.5'));
    $results = $query->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Filter on decimal field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(6)), array_keys($results->getResultItems()), 'Filter on decimal field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    // Regression test for #2284199.
    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 7,
        'type' => 'item',
      ))->save();

    $count = $this->indexItems($this->indexId);
    $this->assertEquals(1, $count, 'Indexing an item with an empty value for a non string field worked.');

    // Regression test for #2471509.
    $this->addField($index, 'body');
    $index->save();
    $this->indexItems($this->indexId);

    \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->create(array(
        'id' => 8,
        'name' => 'Article with long body',
        'type' => 'article',
        'body' => 'astringlongerthanfiftycharactersthatcantbestoredbythedbbackend',
      ))->save();
    $count = $this->indexItems($this->indexId);
    $this->assertEquals(1, $count, 'Indexing an item with a word longer than 50 characters worked.');

    // Regression test for #2616268.
    $index = $this->getIndex();
    $field = $index->getField('body')->setType('string');
    $index->addField($field)->save();
    $count = $this->indexItems($this->indexId);
    $this->assertEquals(8, $count, 'Switching type from text to string worked.');

    // For a string field, 50 characters shouldn't be a problem.
    $query = $this->buildSearch(NULL, array('body,astringlongerthanfiftycharactersthatcantbestoredbythedbbackend'));
    $results = $query->execute();
    $this->assertEquals(1, $results->getResultCount(), 'Filter on new string field returned correct number of results.');
    $this->assertEquals($this->getItemIds(array(8)), array_keys($results->getResultItems()), 'Filter on new string field returned correct result.');
    $this->assertIgnored($results);
    $this->assertWarnings($results);

    $index->removeField('body');
    $index->save();
  }

  /**
   * Tests whether removing the configuration again works as it should.
   */
  protected function checkModuleUninstall() {
    $db_info = $this->getIndexDbInfo();
    $normalized_storage_table = $db_info['index_table'];
    $field_tables = $db_info['field_tables'];

    // See whether clearing the server works.
    // Regression test for #2156151.
    $server = $this->getServer();
    $index = $this->getIndex();
    $server->deleteAllIndexItems($index);
    $query = $this->buildSearch();
    $results = $query->execute();
    $this->assertEquals(0, $results->getResultCount(), 'Clearing the server worked correctly.');
    $this->assertTrue(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were left in place.');

    // Remove first the index and then the server.
    $index->setServer();
    $index->save();

    $db_info = $this->getIndexDbInfo();
    $this->assertNull($db_info, 'The index was successfully removed from the server.');
    $this->assertFalse(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $this->assertFalse(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
    }

    // Re-add the index to see if the associated tables are also properly
    // removed when the server is deleted.

    $index->setServer($server);
    $index->save();
    $server->delete();

    $db_info = $this->getIndexDbInfo();
    $this->assertNull($db_info, 'The index was successfully removed from the server.');
    $this->assertFalse(Database::getConnection()->schema()->tableExists($normalized_storage_table), 'The index tables were deleted.');
    foreach ($field_tables as $field_table) {
      $this->assertFalse(\Drupal::database()->schema()->tableExists($field_table['table']), new FormattableMarkup('Field table %table exists', array('%table' => $field_table['table'])));
    }

    // Uninstall the module.
    \Drupal::service('module_installer')->uninstall(array('search_api_db'), FALSE);
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('search_api_db'), 'The Database Search module was successfully uninstalled.');

    $tables = \Drupal::database()->schema()->findTables('search_api_db_%');
    $this->assertEquals(array(), $tables, 'All the tables of the the Database Search module have been removed.');
  }

  /**
   * Asserts ignored fields from a set of search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The results to check.
   * @param array $ignored
   *   (optional) The ignored keywords that should be present, if any.
   * @param string $message
   *   (optional) The message to be displayed with the assertion.
   */
  protected function assertIgnored(ResultSetInterface $results, array $ignored = array(), $message = 'No keys were ignored.') {
    $this->assertEquals($ignored, $results->getIgnoredSearchKeys(), $message);
  }

  /**
   * Asserts warnings from a set of search results.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The results to check.
   * @param array $warnings
   *   (optional) The ignored warnings that should be present, if any.
   * @param string $message
   *   (optional) The message to be displayed with the assertion.
   */
  protected function assertWarnings(ResultSetInterface $results, array $warnings = array(), $message = 'No warnings were displayed.') {
    $this->assertEquals($warnings, $results->getWarnings(), $message);
  }

  /**
   * Retrieves the search server used by this test.
   *
   * @return \Drupal\search_api\ServerInterface
   *   The search server.
   */
  protected function getServer() {
    return Server::load($this->serverId);
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
   * Adds a field to a search index.
   *
   * The index will not be saved automatically.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   * @param string $property_name
   *   The property's name.
   * @param string $type
   *   (optional) The field type.
   */
  protected function addField(IndexInterface $index, $property_name, $type = 'text') {
    $field_info = array(
      'label' => $property_name,
      'type' => $type,
      'datasource_id' => 'entity:entity_test',
      'property_path' => $property_name,
    );
    $field = Utility::createField($index, $property_name, $field_info);
    $index->addField($field);
    $index->save();
  }

  /**
   * Resets the entity cache for the specified entity.
   *
   * @param string $type
   *   (optional) The type of entity whose cache should be reset. Either "index"
   *   or "server".
   */
  protected function resetEntityCache($type = 'index') {
    $entity_type_id = 'search_api_' . $type;
    \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->resetCache(array($this->{$type . 'Id'}));
  }

  /**
   * Retrieves the database information for the test index.
   *
   * @return array
   *   The database information stored by the backend for the test index.
   */
  protected function getIndexDbInfo() {
    return \Drupal::keyValue(BackendDatabase::INDEXES_KEY_VALUE_STORE_ID)
      ->get($this->indexId);
  }

}
