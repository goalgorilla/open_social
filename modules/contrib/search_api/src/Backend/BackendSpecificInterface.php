<?php

namespace Drupal\search_api\Backend;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Defines methods common to search servers and backend plugins.
 *
 * This is separate from \Drupal\search_api\Backend\BackendInterface since the
 * CRUD reaction methods in the server entity differ from those for the backend
 * plugin.
 */
interface BackendSpecificInterface {

  /**
   * Returns additional, backend-specific information about this server.
   *
   * This information will be then added to the server's "View" tab in some way.
   * In the default theme implementation the data is output in a table with two
   * columns along with other, generic information about the server.
   *
   * @return array
   *   An array of additional server information, with each piece of information
   *   being an associative array with the following keys:
   *   - label: The human-readable label for this data.
   *   - info: The information, as HTML.
   *   - status: (optional) The status associated with this information. One of
   *     "info", "ok", "warning" or "error". Defaults to "info".
   */
  public function viewSettings();

  /**
   * Returns a boolean with the availability of the backend.
   *
   * This can implement a specific call to test if the backend is available for
   * reading. For SOLR or elasticsearch this would be a "ping" to the server to
   * test if it's online. If this is a db-backend that is running on a separate
   * server, this can also be a ping. When it's a db-backend that runs in the
   * same database as the drupal installation; just returning TRUE is enough.
   *
   * @return bool
   *   The availability of the backend.
   */
  public function isAvailable();

  /**
   * Determines whether the backend supports a given feature.
   *
   * Features are optional extensions to Search API functionality and usually
   * defined and used by third-party modules.
   *
   * There are currently three features defined directly in the Search API
   * project:
   * - search_api_facets, by the search_api_facetapi module.
   * - search_api_facets_operator_or, also by the search_api_facetapi module.
   * - search_api_mlt, by the search_api_views module.
   *
   * @param string $feature
   *   The name of the optional feature.
   *
   * @return bool
   *   TRUE if the backend knows and supports the specified feature, FALSE
   *   otherwise.
   */
  public function supportsFeature($feature);

  /**
   * Determines whether the backend supports a given add-on data type.
   *
   * @param string $type
   *   The identifier of the add-on data type.
   *
   * @return bool
   *   TRUE if the backend supports that data type.
   */
  public function supportsDataType($type);

  /**
   * Adds a new index to this server.
   *
   * If the index was already added to the server, the object should treat this
   * as if removeIndex() and then addIndex() were called.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to add.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while adding the index.
   */
  public function addIndex(IndexInterface $index);

  /**
   * Notifies the server that an index attached to it has been changed.
   *
   * If any user action is necessary as a result of this, the method should
   * use drupal_set_message() to notify the user.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The updated index.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while reacting to the change.
   */
  public function updateIndex(IndexInterface $index);

  /**
   * Removes an index from this server.
   *
   * This might mean that the index has been deleted, or reassigned to a
   * different server. If you need to distinguish between these cases, inspect
   * $index->getServerId().
   *
   * If the index wasn't added to the server previously, the method call should
   * be ignored.
   *
   * Implementations of this method should also check whether
   * $index->isReadOnly() and don't delete any indexed data if it is.
   *
   * @param \Drupal\search_api\IndexInterface|string $index
   *   Either an object representing the index to remove, or its ID (if the
   *   index was completely deleted).
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while removing the index.
   */
  public function removeIndex($index);

  /**
   * Indexes the specified items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index for which items should be indexed.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to be indexed, keyed by their item IDs.
   *   The value of fields with the "tokenized_text" type is an array of tokens.
   *   Each token is an array containing the following keys:
   *   - value: The word that the token represents.
   *   - score: A score for the importance of that word.
   *
   * @return string[]
   *   The IDs of all items that were successfully indexed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if indexing was prevented by a fundamental configuration error.
   */
  public function indexItems(IndexInterface $index, array $items);

  /**
   * Deletes the specified items from the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index from which items should be deleted.
   * @param string[] $item_ids
   *   The IDs of the deleted items.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while trying to delete the items.
   */
  public function deleteItems(IndexInterface $index, array $item_ids);

  /**
   * Deletes all the items from the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which items should be deleted.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred while trying to delete indexed items.
   */
  public function deleteAllIndexItems(IndexInterface $index);

  /**
   * Executes a search on this server.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to execute.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   The search results.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error prevented the search from completing.
   */
  public function search(QueryInterface $query);

}
