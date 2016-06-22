<?php

namespace Drupal\search_api;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\Tracker\TrackerInterface;

/**
 * Defines the interface for index entities.
 */
interface IndexInterface extends ConfigEntityInterface {

  /**
   * String used to separate a datasource prefix from the rest of an identifier.
   *
   * Internal field identifiers of datasource-dependent fields in the Search API
   * consist of two parts: the ID of the datasource to which the field belongs;
   * and the property path to the field, with properties separated by colons.
   * The two parts are concatenated using this character as a separator to form
   * the complete field identifier. (In the case of datasource-independent
   * fields, the identifier doesn't contain the separator.)
   *
   * Likewise, internal item IDs consist of the datasource ID and the item ID
   * within that datasource, separated by this character.
   */
  const DATASOURCE_ID_SEPARATOR = '/';

  /**
   * Retrieves the index description.
   *
   * @return string
   *   The description of this index.
   */
  public function getDescription();

  /**
   * Determines whether this index is read-only.
   *
   * @return bool
   *   TRUE if this index is read-only, otherwise FALSE.
   */
  public function isReadOnly();

  /**
   * Gets the cache ID prefix used for this index's caches.
   *
   * @param string $sub_id
   *   An ID for the particular cache within the index that should be
   *   identified.
   *
   * @return string
   *   The cache ID (prefix) for this index's caches.
   */
  public function getCacheId($sub_id);

  /**
   * Retrieves an option.
   *
   * @param string $name
   *   The name of an option.
   * @param mixed $default
   *   The value return if the option wasn't set.
   *
   * @return mixed
   *   The value of the option.
   *
   * @see getOptions()
   */
  public function getOption($name, $default = NULL);

  /**
   * Retrieves an array of all options.
   *
   * The following options are known:
   * - cron_limit: The maximum number of items to be indexed per cron batch.
   * - index_directly: Boolean setting whether entities are indexed immediately
   *   after they are created or updated.
   * - fields: An array of all indexed fields for this index. Keys are the field
   *   identifiers, the values are arrays for specifying the field settings. The
   *   structure of those arrays looks like this:
   *   - type: The type set for this field. One of the types returned by
   *     \Drupal\search_api\Utility::getDefaultDataTypes().
   *   - boost: (optional) A boost value for terms found in this field during
   *     searches. Usually only relevant for fulltext fields. Defaults to 1.0.
   * - processors: An array of all processors available for the index. The keys
   *   are the processor identifiers, the values are arrays containing the
   *   settings for that processor. The inner structure looks like this:
   *   - status: Boolean indicating whether the processor is enabled.
   *   - weight: Used for sorting the processors.
   *   - settings: Processor-specific settings, configured via the processor's
   *     configuration form.
   *
   * @return array
   *   An associative array of option values, keyed by the option name.
   */
  public function getOptions();

  /**
   * Sets an option.
   *
   * @param string $name
   *   The name of an option.
   * @param mixed $option
   *   The new option.
   *
   * @return $this
   */
  public function setOption($name, $option);

  /**
   * Sets the index's options.
   *
   * @param array $options
   *   The new index options.
   *
   * @return $this
   */
  public function setOptions(array $options);

  /**
   * Sets this index's datasource plugins.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface[] $datasources
   *   An array of datasources
   *
   * @return $this
   */
  public function setDatasources(array $datasources);

  /**
   * Retrieves the IDs of all datasources enabled for this index.
   *
   * @return string[]
   *   The IDs of the datasource plugins used by this index.
   */
  public function getDatasourceIds();

  /**
   * Determines whether the given datasource ID is valid for this index.
   *
   * The general contract of this method is that it should return TRUE if, and
   * only if, a call to getDatasource() with the same ID would not result in an
   * exception.
   *
   * @param string $datasource_id
   *   A datasource plugin ID.
   *
   * @return bool
   *   TRUE if the datasource with the given ID is enabled for this index and
   *   can be loaded. FALSE otherwise.
   */
  public function isValidDatasource($datasource_id);

  /**
   * Retrieves a specific datasource plugin for this index.
   *
   * @param string $datasource_id
   *   The ID of the datasource plugin to return.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface
   *   The datasource plugin with the given ID.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the specified datasource isn't enabled for this index, or
   *   couldn't be loaded.
   */
  public function getDatasource($datasource_id);

  /**
   * Retrieves this index's datasource plugins.
   *
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface[]
   *   The datasource plugins used by this index, keyed by plugin ID.
   */
  public function getDatasources($only_enabled = TRUE);

  /**
   * Determines whether the tracker is valid.
   *
   * @return bool
   *   TRUE if the tracker is valid, otherwise FALSE.
   */
  public function hasValidTracker();

  /**
   * Retrieves the tracker plugin's ID.
   *
   * @return string
   *   The ID of the tracker plugin used by this index.
   */
  public function getTrackerId();

  /**
   * Retrieves the tracker plugin.
   *
   * @return \Drupal\search_api\Tracker\TrackerInterface
   *   The index's tracker plugin.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the tracker couldn't be instantiated.
   */
  public function getTrackerInstance();

  /**
   * Sets the tracker the index uses.
   *
   * @param \Drupal\search_api\Tracker\TrackerInterface $tracker
   *   The new tracker for the index.
   *
   * @return $this
   */
  public function setTracker(TrackerInterface $tracker);

  /**
   * Determines whether this index is lying on a valid server.
   *
   * @return bool
   *   TRUE if the index's server is set and valid, otherwise FALSE.
   */
  public function hasValidServer();

  /**
   * Checks if this index has an enabled server.
   *
   * @return bool
   *   TRUE if this index is attached to a valid, enabled server.
   */
  public function isServerEnabled();

  /**
   * Retrieves the ID of the server the index is attached to.
   *
   * @return string|null
   *   The index's server's ID, or NULL if the index doesn't have a server.
   */
  public function getServerId();

  /**
   * Retrieves the server the index is attached to.
   *
   * @return \Drupal\search_api\ServerInterface|null
   *   The server this index is linked to, or NULL if the index doesn't have a
   *   server.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the server couldn't be loaded.
   */
  public function getServerInstance();

  /**
   * Sets the server the index is attached to.
   *
   * @param \Drupal\search_api\ServerInterface|null $server
   *   The server to move this index to, or NULL.
   */
  public function setServer(ServerInterface $server = NULL);

  /**
   * Loads this index's processors.
   *
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\search_api\Processor\ProcessorInterface[]
   *   An array of all enabled (or available, if $only_enabled is FALSE)
   *   processors for this index.
   */
  public function getProcessors($only_enabled = TRUE);

  /**
   * Loads this index's processors for a specific stage.
   *
   * @param string $stage
   *   The stage for which to return the processors. One of the
   *   \Drupal\search_api\Processor\ProcessorInterface::STAGE_* constants.
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\search_api\Processor\ProcessorInterface[]
   *   An array of all enabled (or available, if if $only_enabled is FALSE)
   *   processors that support the given stage, ordered by the weight for that
   *   stage.
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE);

  /**
   * Adds a processor to this index.
   *
   * @param \Drupal\search_api\Processor\ProcessorInterface $processor
   *   The processor to be added.
   *
   * @return $this
   */
  public function addProcessor(ProcessorInterface $processor);

  /**
   * Removes a processor from this index.
   *
   * @param string $processor_id
   *   The ID of the processor to remove.
   *
   * @return $this
   */
  public function removeProcessor($processor_id);

  /**
   * Preprocesses data items for indexing.
   *
   * Lets all enabled processors for this index preprocess the indexed data.
   *
   * @param array $items
   *   An array of items to be preprocessed for indexing.
   */
  public function preprocessIndexItems(array &$items);

  /**
   * Preprocesses a search query.
   *
   * Lets all enabled processors for this index preprocess the search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query to be executed.
   */
  public function preprocessSearchQuery(QueryInterface $query);

  /**
   * Postprocesses search results before they are displayed.
   *
   * If a class is used for both pre- and post-processing a search query, the
   * same object will be used for both calls (so preserving some data or state
   * locally is possible).
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The search results.
   */
  public function postprocessSearchResults(ResultSetInterface $results);

  /**
   * Adds a field to this index.
   *
   * If the field is already present (with the same datasource and property
   * path) its settings will be updated.
   *
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field to add, or update.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field could not be added, either because a different field
   *   with the same field ID would be overwritten, or because the field
   *   identifier is one of the pseudo-fields that can be used in search
   *   queries.
   */
  public function addField(FieldInterface $field);

  /**
   * Changes the field ID of a field.
   *
   * @param string $old_field_id
   *   The old ID of the field.
   * @param string $new_field_id
   *   The new ID of the field.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if no field with the old ID exists, or because the new ID is
   *   already taken, or because the new field ID is one of the pseudo-fields
   *   that can be used in search queries.
   */
  public function renameField($old_field_id, $new_field_id);

  /**
   * Removes a field from the index.
   *
   * If the field doesn't exist, the call will fail silently.
   *
   * @param string $field_id
   *   The ID of the field to remove.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field is locked.
   */
  public function removeField($field_id);

  /**
   * Returns a list of all indexed fields of this index.
   *
   * @return \Drupal\search_api\Item\FieldInterface[]
   *   An array of all indexed fields for this index, keyed by field identifier.
   */
  public function getFields();

  /**
   * Returns a field from this index.
   *
   * @param string $field_id
   *   The field identifier.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   The field with the given field identifier, or NULL if there is no such
   *   field.
   */
  public function getField($field_id);

  /**
   * Returns a list of all indexed fields of a specific datasource.
   *
   * @param string|null $datasource_id
   *   The ID of the datasource whose fields should be retrieved, or NULL to
   *   retrieve all datasource-independent fields.
   *
   * @return \Drupal\search_api\Item\FieldInterface[]
   *   An array of all indexed fields for the given datasource, keyed by field
   *   identifier.
   */
  public function getFieldsByDatasource($datasource_id);

  /**
   * Retrieves all of this index's fulltext fields.
   *
   * @return string[]
   *   An array containing the field identifiers of all indexed fulltext fields
   *   available for this index.
   */
  public function getFulltextFields();

  /**
   * Retrieves the properties of one of this index's datasources.
   *
   * @param string|null $datasource_id
   *   The ID of the datasource for which the properties should be retrieved. Or
   *   NULL to retrieve all datasource-independent properties.
   * @param bool $alter
   *   (optional) Whether to pass the property definitions to the index's
   *   enabled processors for altering before returning them.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The properties belonging to the given datasource that are available in
   *   this index, keyed by their property names (not the complete field IDs).
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the specified datasource isn't enabled for this index, or
   *   couldn't be loaded.
   */
  public function getPropertyDefinitions($datasource_id, $alter = TRUE);

  /**
   * Loads a single search object of this index.
   *
   * @param string $item_id
   *   The internal item ID of the object, with datasource prefix.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface|null
   *   The loaded object, or NULL if the item does not exist.
   */
  public function loadItem($item_id);

  /**
   * Loads multiple search objects for this index.
   *
   * @param array $item_ids
   *   The internal item IDs of the objects, with datasource prefix.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface[]
   *   The loaded items, keyed by their internal item IDs.
   */
  public function loadItemsMultiple(array $item_ids);

  /**
   * Indexes a set amount of items.
   *
   * Will fetch the items to be indexed from the datasources and send them to
   * indexItems(). It will then mark all successfully indexed items as such in
   * the datasource.
   *
   * @param int $limit
   *   (optional) The maximum number of items to index, or -1 to index all
   *   items.
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   indexed. Otherwise, items from any datasource are indexed.
   *
   * @return int
   *   The number of items successfully indexed.
   */
  public function indexItems($limit = -1, $datasource_id = NULL);

  /**
   * Indexes some objects on this index.
   *
   * Will return the IDs of items that were marked as indexed – i.e., items that
   * were either rejected from indexing (by a processor or alter hook) or were
   * successfully indexed.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface[] $search_objects
   *   An array of search objects to be indexed, keyed by their item IDs.
   *
   * @return string[]
   *   The IDs of all items that should be marked as indexed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any error occurred during indexing.
   */
  public function indexSpecificItems(array $search_objects);

  /**
   * Adds items from a specific datasource to the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific item IDs.
   */
  public function trackItemsInserted($datasource_id, array $ids);

  /**
   * Updates items from a specific datasource present in the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific item IDs.
   */
  public function trackItemsUpdated($datasource_id, array $ids);

  /**
   * Deletes items from the index.
   *
   * Note that this method receives datasource-specific item IDs as the
   * parameter, not containing the datasource prefix.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific items IDs.
   */
  public function trackItemsDeleted($datasource_id, array $ids);

  /**
   * Marks all items in this index for reindexing.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an internal error prevented the operation from succeeding.
   *   E.g., if the tracker couldn't be loaded.
   */
  public function reindex();

  /**
   * Clears all indexed data from this index and marks it for reindexing.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the server couldn't be loaded, for example.
   */
  public function clear();

  /**
   * Determines whether reindexing has been triggered in this page request.
   *
   * @return bool
   *   TRUE if reindexing for this index has been triggered in this page
   *   request, and no items have been indexed since; FALSE otherwise. In other
   *   words, this returns FALSE if and only if calling reindex() on this index
   *   would have any effect (or if it is disabled).
   */
  public function isReindexing();

  /**
   * Creates a query object for this index.
   *
   * @param array $options
   *   (optional) Associative array of options configuring this query.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A query object for searching this index.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the index is currently disabled or its server doesn't exist.
   *
   * @see \Drupal\search_api\Query\QueryInterface::create()
   */
  public function query(array $options = array());

}
