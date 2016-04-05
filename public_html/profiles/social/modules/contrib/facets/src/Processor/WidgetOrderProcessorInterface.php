<?php

namespace Drupal\facets\Processor;

/**
 * Processor runs before the renderable array is created.
 */
interface WidgetOrderProcessorInterface extends BuildProcessorInterface {

  /**
   * Orders results and return the new order of results.
   *
   * @param \Drupal\facets\Result\Result[] $results
   *   An array containing results.
   * @param string $order
   *   A string denoting the order in which we should sort, either 'ASC' or
   *   'DESC'.
   *
   * @return \Drupal\facets\Result\Result[]
   *   The same array that was passed in, ordered by $order
   */
  public function sortResults(array $results, $order = 'ASC');

}
