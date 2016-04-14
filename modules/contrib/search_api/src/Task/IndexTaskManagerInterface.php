<?php

namespace Drupal\search_api\Task;

use Drupal\search_api\IndexInterface;

/**
 * Defines the interface for the index task manager.
 */
interface IndexTaskManagerInterface {

  /**
   * Creates a task to start tracking for the given index, or some datasources.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   * @param string[]|null $datasource_ids
   *   (optional) The IDs of specific datasources for which tracking should
   *   start. Or NULL to start tracking for all datasources.
   */
  public function startTracking(IndexInterface $index, array $datasource_ids = NULL);

  /**
   * Adds a single page of items to the tracker.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return int|null
   *   The number of items tracked. Or NULL if no items were added and tracking
   *   for this index has been completed. (Note that 0 can also be returned,
   *   which does not mean that tracking has been completed for the index.)
   */
  public function addItemsOnce(IndexInterface $index);

  /**
   * Sets a batch to track all remaining items for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   */
  public function addItemsBatch(IndexInterface $index);

  /**
   * Tracks all remaining items for the given index.
   *
   * Since no kind of batch processing is used, this might run out of memory or
   * execution time on larger sites.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return int|null
   *   The number of items tracked. Or NULL if no items were added and tracking
   *   for this index has been completed. (Note that 0 can also be returned,
   *   which does not mean that tracking has been completed for the index.)
   */
  public function addItemsAll(IndexInterface $index);

  /**
   * Stops tracking for the given index.
   *
   * Will delete any remaining tracking tasks and also remove all items from
   * tracking for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   * @param string[]|null $datasource_ids
   *   (optional) The IDs of the datasources for which to stop tracking. Or NULL
   *   to stop tracking for all datasources.
   */
  public function stopTracking(IndexInterface $index, array $datasource_ids = NULL);

  /**
   * Checks whether tracking has already been completed for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return bool
   *   TRUE if tracking has been completed for the given index, FALSE otherwise.
   */
  public function isTrackingComplete(IndexInterface $index);

}
