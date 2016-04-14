<?php

namespace Drupal\search_api\Query;

/**
 * Represents a search results cache.
 */
interface ResultsCacheInterface {

  /**
   * Adds a result set to the cache.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The search results to cache.
   */
  public function addResults(ResultSetInterface $results);

  /**
   * Retrieves the results data for a search ID.
   *
   * @param string $search_id
   *   The search ID of the results to retrieve.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface|null
   *   The results with the given search ID, if present; NULL otherwise.
   */
  public function getResults($search_id);

  /**
   * Removes the result set with the given search ID from the cache.
   *
   * @param string $search_id
   *   The search ID.
   */
  public function removeResults($search_id);

}
