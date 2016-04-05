<?php

namespace Drupal\facets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * The facet source entity.
 */
interface FacetSourceInterface extends ConfigEntityInterface {

  /**
   * Returns the label of the facet source.
   *
   * @return string
   *   The facet name.
   */
  public function getName();

  /**
   * Returns the filter key for this facet source.
   *
   * @return string
   *   The filter key.
   */
  public function getFilterKey();

  /**
   * Sets the filter key for this facet source.
   *
   * @param string $filter_key
   *   The filter key.
   */
  public function setFilterKey($filter_key);

  /**
   * Sets the processor name to be used.
   *
   * @param string $processor_name
   *   Plugin name of the url processor.
   */
  public function setUrlProcessor($processor_name);

  /**
   * Returns a string version of the url processor.
   *
   * @return string
   *   The url processor to be used as a string.
   */
  public function getUrlProcessorName();

}
