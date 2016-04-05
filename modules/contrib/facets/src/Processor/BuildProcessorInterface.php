<?php

namespace Drupal\facets\Processor;

use Drupal\facets\FacetInterface;

/**
 * Processor runs before the renderable array is created.
 */
interface BuildProcessorInterface extends ProcessorInterface {

  /**
   * Runs before the renderable array is created.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet being changed.
   * @param \Drupal\facets\Result\Result[] $results
   *   The results being changed.
   *
   * @return \Drupal\facets\Result\Result[] $results
   *   The changed results.
   */
  public function build(FacetInterface $facet, array $results);

}
