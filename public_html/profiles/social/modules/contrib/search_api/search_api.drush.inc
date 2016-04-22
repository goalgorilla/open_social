<?php

/**
 * @file
 * Drush commands for Search API.
 */

use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\IndexBatchHelper;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;

/**
 * Implements hook_drush_command().
 */
function search_api_drush_command() {
  $items = array();

  $items['search-api-list'] = array(
    'description' => 'List all search indexes.',
    'examples' => array(
      'drush search-api-list' => dt('List all search indexes.'),
      'drush sapi-l' => dt('Alias to list all search indexes.'),
    ),
    'aliases' => array('sapi-l'),
  );

  $items['search-api-enable'] = array(
    'description' => 'Enable one or more disabled search indexes.',
    'examples' => array(
      'drush search-api-enable node_index' => dt('Enable the search index with the ID @name.', array('@name' => 'node_index')),
      'drush sapi-en node_index' => dt('Alias to enable the search index with the ID @name.', array('@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index to enable.'),
    ),
    'aliases' => array('sapi-en'),
  );

  $items['search-api-enable-all'] = array(
    'description' => 'Enable all disabled search indexes.',
    'examples' => array(
      'drush search-api-enable-all' => dt('Enable all disabled indexes.'),
      'drush sapi-ena' => dt('Alias to enable all disabled indexes.'),
    ),
    'arguments' => array(),
    'aliases' => array('sapi-ena'),
  );

  $items['search-api-disable'] = array(
    'description' => 'Disable one or more enabled search indexes.',
    'examples' => array(
      'drush search-api-disable node_index' => dt('Disable the search index with the ID @name.', array('@name' => 'node_index')),
      'drush sapi-dis node_index' => dt('Alias to disable the search index with the ID @name.', array('@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index to disable.'),
    ),
    'aliases' => array('sapi-dis'),
  );

  $items['search-api-disable-all'] = array(
    'description' => 'Disable all enabled search indexes.',
    'examples' => array(
      'drush search-api-disable-all' => dt('Disable all enabled indexes.'),
      'drush sapi-disa' => dt('Alias to disable all enabled indexes.'),
    ),
    'arguments' => array(),
    'aliases' => array('sapi-disa'),
  );

  $items['search-api-status'] = array(
    'description' => 'Show the status of one or all search indexes.',
    'examples' => array(
      'drush search-api-status' => dt('Show the status of all search indexes.'),
      'drush sapi-s' => dt('Alias to show the status of all search indexes.'),
      'drush sapi-s node_index' => dt('Show the status of the search index with the ID @name.', array('@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index to view.'),
    ),
    'aliases' => array('sapi-s'),
  );

  $items['search-api-index'] = array(
    'description' => 'Index items for one or all enabled search indexes.',
    'examples' => array(
      'drush search-api-index' => dt('Index all items for all enabled indexes.'),
      'drush sapi-i' => dt('Alias to index all items for all enabled indexes.'),
      'drush sapi-i node_index' => dt('Index all items for the index with the ID @name.', array('@name' => 'node_index')),
      'drush sapi-i node_index 100' => dt('Index a maximum number of @limit items for the index with the ID @name.', array('@limit' => 100, '@name' => 'node_index')),
      'drush sapi-i node_index 100 10' => dt('Index a maximum number of @limit items (@batch_size items per batch run) for the index with the ID @name.', array('@limit' => 100, '@batch_size' => 10, '@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index.'),
      'limit' => dt('The number of items to index. Set to 0 to index all items. Defaults to 0 (index all).'),
      'batch_size' => dt('The number of items to index per batch run. Set to 0 to index all items at once. Defaults to the "@batch_size" setting of the index.', array('@batch_size' => dt('Cron batch size'))),
    ),
    'aliases' => array('sapi-i'),
  );

  $items['search-api-reindex'] = array(
    'description' => 'Force reindexing of one or all search indexes, without deleting existing index data.',
    'examples' => array(
      'drush search-api-reindex' => dt('Schedule all search indexes for reindexing.'),
      'drush sapi-r' => dt('Alias to schedule all search indexes for reindexing .'),
      'drush sapi-r node_index' => dt('Schedule the search index with the ID @name for reindexing.', array('@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index.'),
    ),
    'aliases' => array('sapi-r'),
  );

  $items['search-api-clear'] = array(
    'description' => 'Clear one or all search indexes and mark them for reindexing.',
    'examples' => array(
      'drush search-api-clear' => dt('Clear all search indexes.'),
      'drush sapi-c' => dt('Alias to clear all search indexes.'),
      'drush sapi-c node_index' => dt('Clear the search index with the ID @name.', array('@name' => 'node_index')),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index.'),
    ),
    'aliases' => array('sapi-c'),
  );

  $items['search-api-search'] = array(
    'description' => 'Search for a keyword or phrase in a given index.',
    'examples' => array(
      'drush search-api-search node_index title' => dt('Search for "title" inside the "node_index" index.'),
      'drush sapi-search node_index title' => dt('Alias to search for "title" inside the "node_index" index.'),
    ),
    'arguments' => array(
      'index_id' => dt('The ID of an index.'),
      'keyword' => dt('The keyword to look for.'),
    ),
    'aliases' => array('sapi-search'),
  );

  $items['search-api-server-list'] = array(
    'description' => 'List all search servers.',
    'examples' => array(
      'drush search-api-server-list' => dt('List all search servers.'),
      'drush sapi-sl' => dt('Alias to list all search servers.'),
    ),
    'aliases' => array('sapi-sl'),
  );

  $items['search-api-server-enable'] = array(
    'description' => 'Enable a search server.',
    'examples' => array(
      'drush search-api-server-e my_solr_server' => dt('Enable the @server search server.', array('@server' => 'my_solr_server')),
      'drush sapi-se my_solr_server' => dt('Alias to enable the @server search server.', array('@server' => 'my_solr_server')),
    ),
    'arguments' => array(
      'server_id' => dt('The numeric ID or machine name of a search server to enable.'),
    ),
    'aliases' => array('sapi-se'),
  );

  $items['search-api-server-disable'] = array(
    'description' => 'Disable a search server.',
    'examples' => array(
      'drush search-api-server-disable' => dt('Disable the @server search server.', array('@server' => 'my_solr_server')),
      'drush sapi-sd' => dt('Alias to disable the @server search server.', array('@server' => 'my_solr_server')),
    ),
    'arguments' => array(
      'server_id' => dt('The numeric ID or machine name of a search server to disable.'),
    ),
    'aliases' => array('sapi-sd'),
  );

  $items['search-api-set-index-server'] = array(
    'description' => 'Set the search server used by a given index.',
    'examples' => array(
      'drush search-api-set-index-server default_node_index my_solr_server' => dt('Set the @index index to used the @server server.', array('@index' => 'default_node_index', '@server' => 'my_solr_server')),
      'drush sapi-sis default_node_index my_solr_server' => dt('Alias to set the @index index to used the @server server.', array('@index' => 'default_node_index', '@server' => 'my_solr_server')),
    ),
    'arguments' => array(
      'index_id' => dt('The machine name of an index.'),
      'server_id' => dt('The machine name of a server to set on the index.'),
    ),
    'aliases' => array('sapi-sis'),
  );

  return $items;
}

/**
 * Prints a list of all search indexes.
 */
function drush_search_api_list() {
  $indexes = search_api_drush_get_indexes();
  if (!$indexes) {
    return;
  }

  $rows[] = array(
    dt('ID'),
    dt('Name'),
    dt('Server'),
    dt('Type'),
    dt('Status'),
    dt('Limit'),
  );
  $none = '(' . dt('none') . ')';
  $enabled = dt('enabled');
  $disabled = dt('disabled');

  foreach ($indexes as $index) {
    $types = array();
    foreach ($index->getDatasources() as $datasource) {
      $types[] = $datasource->label();
    }
    $rows[] = array(
      $index->id(),
      $index->label(),
      $index->getServerId() ? $index->getServerInstance()->label() : $none,
      $types ? implode(', ', $types) : $none,
      $index->status() ? $enabled : $disabled,
      (int) $index->getOption('cron_limit'),
    );
  }

  drush_print_table($rows);
}

/**
 * Enables one or more search indexes.
 *
 * @param string|null …
 *   The ID of a search index to be enabled. Or NULL (only used internally) to
 *   enable all disabled indexes.
 */
function drush_search_api_enable() {
  if (!search_api_drush_get_index_count()) {
    drush_set_error(dt('There are no indexes defined. Please define an index before trying to enable it.'));
    return;
  }

  $index_ids = func_get_args();

  if (!$index_ids) {
    drush_set_error(dt('You must specify at least one index to enable. The indexes I know about are:'));
    drush_search_api_list();
  }

  foreach ($index_ids as $index_id) {
    $indexes = search_api_drush_get_indexes($index_id);
    if (!$indexes) {
      continue;
    }
    foreach ($indexes as $index) {
      if ($index_id || !$index->status()) {
        search_api_drush_set_index_state($index);
      }
    }
  }
}

/**
 * Enables all search indexes.
 */
function drush_search_api_enable_all() {
  drush_search_api_enable(NULL);
}

/**
 * Disables one or more search indexes.
 *
 * @param string|null …
 *   The ID of a search index to be disabled. Or NULL (only used internally) to
 *   disable all disabled indexes.
 */
function drush_search_api_disable() {
  if (!search_api_drush_get_index_count()) {
    drush_set_error(dt('There are no indexes defined. Please define an index before trying to disable it.'));
    return;
  }

  $index_ids = func_get_args();

  if (!$index_ids) {
    drush_set_error(dt('You must specify at least one index to disable. The indexes I know about are:'));
    drush_search_api_list();
  }

  foreach ($index_ids as $index_id) {
    $indexes = search_api_drush_get_indexes($index_id);
    foreach ($indexes as $index) {
      if ($index->status()) {
        search_api_drush_set_index_state($index, FALSE);
      }
    }
  }
}

/**
 * Disables all search indexes.
 */
function drush_search_api_disable_all() {
  drush_search_api_disable(NULL);
}

/**
 * Displays the status of one or all search indexes.
 *
 * @param string|null $index_id
 *   (optional) The ID of the search index whose status should be displayed, or
 *   NULL to display the status of all search indexes.
 */
function drush_search_api_status($index_id = NULL) {
  $indexes = search_api_drush_get_indexes($index_id);
  if (!$indexes) {
    return;
  }

  $rows[] = array(
    dt('ID'),
    dt('Name'),
    dt('% Complete'),
    dt('Indexed'),
    dt('Total'),
  );

  foreach ($indexes as $index) {
    $indexed = $index->getTrackerInstance()->getIndexedItemsCount();
    $total = $index->getTrackerInstance()->getTotalItemsCount();

    $complete = '-';
    if ($total > 0) {
      $complete = (100 * round($indexed / $total, 3)) . '%';
    }

    $row = array(
      $index->id(),
      $index->label(),
      $complete,
      $indexed,
      $total,
    );
    $rows[] = $row;
  }

  drush_print_table($rows);
}

/**
 * Indexes items.
 *
 * @param string|null $index_id
 *   (optional) The index ID for which items should be indexed, or NULL to index
 *   items on all indexes.
 * @param int|null $limit
 *   (optional) Maximum number of items to index, or NULL to index all items.
 * @param int|null $batch_size
 *   (optional) Number of items to index per batch, or NULL to use the index's
 *   "cron_limit" setting.
 */
function drush_search_api_index($index_id = NULL, $limit = NULL, $batch_size = NULL) {
  $indexes = search_api_drush_get_indexes($index_id);
  if (!$indexes) {
    return;
  }

  foreach ($indexes as $index) {
    $tracker = $index->getTrackerInstance();
    $remaining = $tracker->getTotalItemsCount() - $tracker->getIndexedItemsCount();

    if (!$remaining) {
      drush_log(dt("The index @index is up to date.", array('@index' => $index->label())), 'ok');
      continue;
    }
    else {
      $args = array(
        '@remaining' => $remaining,
        '@limit' => $limit ? $limit : dt('all'),
        '@index' => $index->label(),
      );
      drush_log(dt("Found @remaining items to index for @index. Indexing @limit items.", $args), 'ok');
    }

    // If we pass NULL, it would be used as "no items". -1 is the correct way to
    // index all items.
    $limit = $limit ?: -1;

    // Get the default batch size.
    if (!isset($batch_size)) {
      $cron_limit = $index->getOption('cron_limit');
      $batch_size = $cron_limit ?: \Drupal::configFactory()->get('search_api.settings')->get('default_cron_limit');
    }

    // Get the number items to index.
    if (!isset($limit) || !is_int($limit += 0) || $limit <= 0) {
      $limit = $remaining;
    }

    $arguments = array(
      '@index' => $index->label(),
      '@limit' => $limit,
      '@batch_size' => $batch_size,
    );
    drush_log(dt("Indexing a maximum number of @limit items (@batch_size items per batch run) for the index '@index'.", $arguments), 'ok');

    // Create the batch.
    try {
      IndexBatchHelper::create($index, $batch_size, $limit);
      drush_backend_batch_process();
    }
    catch (SearchApiException $e) {
      drush_log(dt("Couldn't create a batch, please check the batch size and limit parameters."), 'error');
    }

    // @todo Report number of successfully indexed items, and check whether that
    //   matched the expectations.
  }
}

/**
 * Schedules a search index for reindexing.
 *
 * @param string|null $index_id
 *   (optional) The index ID for which items should be reindexed, or NULL to
 *   reindex all search indexes.
 */
function drush_search_api_reindex($index_id = NULL) {
  $indexes = search_api_drush_get_indexes($index_id);
  if (!$indexes) {
    return;
  }
  foreach ($indexes as $index) {
    if ($index->status()) {
      $index->reindex();
      drush_log(dt('@index was successfully marked for reindexing.', array('@index' => $index->label())), 'ok');
    }
  }
}

/**
 * Clears a search index.
 *
 * @param string|null $index_id
 *   (optional) The ID of the search index which should be cleared, or NULL to
 *   clear all search indexes.
 */
function drush_search_api_clear($index_id = NULL) {
  $indexes = search_api_drush_get_indexes($index_id);
  if (!$indexes) {
    return;
  }
  foreach ($indexes as $index) {
    if ($index->status()) {
      $index->clear();
      drush_log(dt('@index was successfully cleared.', array('@index' => $index->label())), 'ok');
    }
  }
}

/**
 * Returns the total number of search indexes.
 *
 * @return int
 *   The number of search indexes on this site.
 */
function search_api_drush_get_index_count() {
  return count(Index::loadMultiple());
}

/**
 * Returns either one or all indexes as an array.
 *
 * If the index is not defined or there are no indexes then an error message
 * is displayed.
 *
 * @param string|null $index_id
 *   (optional) Either the ID of the search index to load, or NULL to load all
 *   indexes.
 *
 * @return \Drupal\search_api\IndexInterface[]
 *   An array of search indexes.
 */
function search_api_drush_get_indexes($index_id = NULL) {
  if (!isset($index_id)) {
    $indexes = Index::loadMultiple();
  }
  else {
    $indexes = Index::load($index_id);
    $indexes = $indexes ? array($index_id => $indexes) : array();
  }

  // If we found no indexes, display an error message.
  if (!$indexes) {
    if (!isset($index_id)) {
      drush_set_error(dt('No indexes present.'));
    }
    else {
      drush_set_error(dt('Invalid index ID "@index_id". The following indexes are defined:', array('@index_id' => $index_id)));
      drush_print();
      drush_search_api_list();
    }
  }

  return $indexes;
}

/**
 * Changes the state of a single index.
 *
 * Displays helpful messages if the state cannot be changed.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The index to be enabled.
 * @param bool $enable
 *   (optional) TRUE to enable, FALSE to disable the index.
 */
function search_api_drush_set_index_state(IndexInterface $index, $enable = TRUE) {
  $state_label = $enable ? dt('enabled') : dt('disabled');
  $method = $enable ? 'enable' : 'disable';

  if ($index->status() == $enable) {
    drush_log(dt("The index @index is already @desired_state.", array('@index' => $index->label(), '@desired_state' => $state_label)), 'ok');
    return;
  }
  if (!$index->getServerId()) {
    drush_log(dt("Index @index could not be @desired_state because it is not bound to any server.", array('@index' => $index->label(), '@desired_state' => $state_label)), 'warning');
    return;
  }

  $index->$method()->save();
  drush_log(dt("The index @index was successfully @desired_state.", array('@index' => $index->label(), '@desired_state' => $state_label)), 'ok');
}

/**
 * Executes a simple keyword search and displays the results in a table.
 *
 * @param string $index_id
 *   The ID of the index being searched.
 * @param string $keyword
 *   The search keyword.
 */
function drush_search_api_search($index_id, $keyword) {
  $indexes = search_api_drush_get_indexes($index_id);
  if (empty($indexes[$index_id])) {
    return;
  }
  $query = $indexes[$index_id]->query();
  if ($keyword) {
    $query->keys($keyword);
  }

  // @todo Add paging?
  $query->range(0, 10);

  $results = $query->execute();

  $rows = array();
  // @todo Extend set of shown data.
  foreach ($results->getResultItems() as $key => $item) {
    $rows[$key]['id'] = $item->getId();
  }

  drush_print_table($rows);
}

/**
 * Lists all available search servers.
 */
function drush_search_api_server_list() {
  $servers = Server::loadMultiple();
  if (empty($servers)) {
    drush_print(dt('There are no servers present.'));
    return;
  }
  $rows[] = array(
    dt('ID'),
    dt('Name'),
    dt('Status'),
  );

  foreach ($servers as $server) {
    $rows[] = array(
      $server->id(),
      $server->label(),
      $server->status() ? dt('enabled') : dt('disabled'),
    );
  }

  drush_print_table($rows);
}

/**
 * Enables a search server.
 *
 * @param string $server_id
 *   The ID of the server to enable.
 */
function drush_search_api_server_enable($server_id = NULL) {
  if (!isset($server_id)) {
    drush_print(dt('Please provide a valid server id.'));
    return;
  }
  $server = Server::load($server_id);
  if (empty($server)) {
    drush_print(dt('The server could not be loaded.'));
    return;
  }
  $server->setStatus(TRUE)->save();
  drush_print(dt('The server was enabled successfully.'));

}

/**
 * Disables a search server.
 *
 * @param string $server_id
 *   The ID of the server to disable.
 */
function drush_search_api_server_disable($server_id = NULL) {
  if (!isset($server_id)) {
    drush_print(dt('Please provide a valid server id.'));
    return;
  }
  $server = Server::load($server_id);
  if (empty($server)) {
    drush_print(dt('The server could not be loaded.'));
    return;
  }
  $server->setStatus(FALSE)->save();
  drush_print(dt('The server was disabled successfully.'));
}

/**
 * Sets the server for a given index.
 *
 * @param string $index_id
 *   The ID of the index whose server should be changed.
 * @param string $server_id
 *   The ID of the new server for the index.
 */
function drush_search_api_set_index_server($index_id = NULL, $server_id = NULL) {
  // Make sure we have parameters to work with.
  if (empty($index_id) || empty($server_id)) {
    drush_log(dt('You must specify both an index and server.'), 'error');
    return;
  }
  // Fetch current index and server data.
  $index = Index::load($index_id);
  $server = Server::load($server_id);
  if (!$index) {
    drush_set_error(dt('Invalid index ID "@index_id". The following indexes are defined:', array('@index_id' => $index_id)));
    drush_print();
    drush_search_api_list();
    return;
  }
  if (!$server) {
    drush_set_error(dt('Invalid server ID "@server_id". The following servers are defined:', array('@server_id' => $server_id)));
    drush_print();
    drush_search_api_server_list();
    return;
  }
  // Set the new server on the index.
  try {
    $index->setServer($server);
    $index->save();
    drush_log(dt('Index @index has been set to use server @server and items have been queued for indexing.', array('@index' => $index_id, '@server' => $server_id)), 'ok');
  }
  catch (SearchApiException $e) {
    drush_log($e->getMessage(), 'error');
    drush_log(dt('There was an error setting index @index to use server @server, or this index is already configured to use this server.', array('@index' => $index_id, '@server' => $server_id)), 'error');
  }
}
