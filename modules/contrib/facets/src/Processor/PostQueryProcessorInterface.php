<?php

namespace Drupal\facets\Processor;


/**
 * Processor runs after the query was executed.
 */
interface PostQueryProcessorInterface extends ProcessorInterface {

  /**
   * Runs after the query was executed.
   *
   * Uses the query results and can alter those results, for example a
   * ValueCallbackProcessor.
   *
   * @param \Drupal\facets\Result\Result[] $results
   *   The results being changed.
   */
  public function postQuery(array $results);

}
