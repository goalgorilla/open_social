<?php

namespace Drupal\search_api\Tracker;

use Drupal\search_api\Plugin\IndexPluginInterface;

/**
 * Defines an interface for index tracker plugins.
 *
 * The tracker of an index is responsible for keeping track of the items indexed
 * in the index, which have changed since they were last indexed, etc.
 *
 * @see \Drupal\search_api\Annotation\SearchApiTracker
 * @see \Drupal\search_api\Tracker\TrackerPluginManager
 * @see \Drupal\search_api\Tracker\TrackerPluginBase
 * @see plugin_api
 */
interface TrackerInterface extends IndexPluginInterface {

  /**
   * Inserts new items into the tracking system for this index.
   *
   * @param string[] $ids
   *   The item IDs of the new search items.
   */
  public function trackItemsInserted(array $ids);

  /**
   * Marks the given items as updated for this index.
   *
   * @param string[] $ids
   *   The item IDs of the updated items.
   */
  public function trackItemsUpdated(array $ids);

  /**
   * Marks all items as updated, or only those of a specific datasource.
   *
   * @param string|null $datasource_id
   *   (optional) If given, only items of that datasource are marked as updated.
   */
  public function trackAllItemsUpdated($datasource_id = NULL);

  /**
   * Marks items as indexed for this index.
   *
   * @param string[] $ids
   *   An array of item IDs.
   */
  public function trackItemsIndexed(array $ids);

  /**
   * Removes items from the tracking system for this index.
   *
   * @param string[]|null $ids
   *   (optional) The item IDs of the deleted items; or NULL to remove all
   *   items.
   */
  public function trackItemsDeleted(array $ids = NULL);

  /**
   * Removes all items from the tracker, or only those of a specific datasource.
   *
   * @param string|null $datasource_id
   *   (optional) If given, only items of that datasource are removed.
   */
  public function trackAllItemsDeleted($datasource_id = NULL);

  /**
   * Retrieves a list of item IDs that need to be indexed.
   *
   * @param int $limit
   *   (optional) The maximum number of items to return. Or a negative value to
   *   return all remaining items.
   * @param string|null $datasource_id
   *   (optional) If specified, only items of the datasource with that ID are
   *   retrieved.
   *
   * @return string[]
   *   The IDs of items that still need to be indexed.
   */
  public function getRemainingItems($limit = -1, $datasource_id = NULL);

  /**
   * Retrieves the total number of items that are being tracked for this index.
   *
   * @param string|null $datasource_id
   *   (optional) The datasource to filter the total number of items by.
   *
   * @return int
   *   The total number of items that are tracked for this index, or for the
   *   given datasource on this index.
   */
  public function getTotalItemsCount($datasource_id = NULL);

  /**
   * Retrieves the number of indexed items for this index.
   *
   * @param string|null $datasource_id
   *   (optional) The datasource to filter the total number of indexed items by.
   *
   * @return int
   *   The number of items that have been indexed in their latest state for this
   *   index, or for the given datasource on this index.
   */
  public function getIndexedItemsCount($datasource_id = NULL);

  /**
   * Retrieves the total number of pending items for this index.
   *
   * @param string|null $datasource_id
   *   (optional) The datasource to filter the total number of pending items by.
   *
   * @return int
   *   The total number of items that still need to be indexed for this index,
   *   or for the given datasource on this index.
   */
  public function getRemainingItemsCount($datasource_id = NULL);

}
