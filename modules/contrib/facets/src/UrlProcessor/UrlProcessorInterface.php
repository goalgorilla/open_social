<?php

namespace Drupal\facets\UrlProcessor;

use Drupal\facets\FacetInterface;

/**
 * Interface UrlProcessorInterface.
 *
 * The url processor takes care of retrieving facet information from the url.
 * It also handles the generation of facet links. This extends the pre query and
 * build processor interfaces, those methods are where the bulk of the work
 * should be done.
 *
 * The facet manager has one url processor.
 *
 * @package Drupal\facets\UrlProcessor
 */
interface UrlProcessorInterface {

  /**
   * Adds urls to the results.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet.
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   An array of results.
   *
   * @return \Drupal\facets\Result\ResultInterface[]
   *   An array of results with added urls.
   */
  public function buildUrls(FacetInterface $facet, array $results);

  /**
   * Sets active items.
   *
   * Makes sure that the items that are already set in the current request are
   * remembered when building the facet's urls.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet that is edited.
   */
  public function setActiveItems(FacetInterface $facet);

  /**
   * Returns the filter key.
   *
   * @return string
   *   A string containing the filter key.
   */
  public function getFilterKey();

}
