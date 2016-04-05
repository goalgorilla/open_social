<?php

namespace Drupal\facets\FacetSource;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\facets\FacetInterface;

/**
 * Describes a source for facet items.
 *
 * A facet source is used to abstract the data source where facets can be added
 * to. A good example of this is a Search API view. There are other possible
 * facet data sources, these all implement the FacetSourcePluginInterface.
 *
 * @see plugin_api
 */
interface FacetSourcePluginInterface extends PluginFormInterface {

  /**
   * Fills the facet entities with results from the facet source.
   *
   * @param \Drupal\facets\FacetInterface[] $facets
   *   The configured facets.
   */
  public function fillFacetsWithResults($facets);

  /**
   * Returns the allowed query types for a given facet for the facet source.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we should get query types for.
   *
   * @return string[]
   *   array of allowed query types
   *
   * @throws \Drupal\facets\Exception\Exception
   *   An error when no query types are found.
   */
  public function getQueryTypesForFacet(FacetInterface $facet);

  /**
   * Returns the path where a facet should link to.
   *
   * @return string
   *   The path of the facet.
   */
  public function getPath();

  /**
   * Returns true if the Facet source is being rendered in the current request.
   *
   * This function will define if all facets for this facet source are shown
   * when facet source visibility: "being rendered" is configured in the facet
   * visibility settings.
   *
   * @return bool
   *   True when the facet is rendered on the same page.
   */
  public function isRenderedInCurrentRequest();

  /**
   * Returns an array of fields that are defined on the facet source.
   *
   * This returns an array of fields that are defined on the source. This array
   * is keyed by the field's machine name and has values of the field's label.
   *
   * @return array
   *   An array of available fields.
   */
  public function getFields();

  /**
   * Sets the search keys, or query text, submitted by the user.
   *
   * @param string $keys
   *   The search keys, or query text, submitted by the user.
   *
   * @return self
   *   An instance of this class.
   */
  public function setSearchKeys($keys);

  /**
   * Returns the search keys, or query text, submitted by the user.
   *
   * @return string
   *   The search keys, or query text, submitted by the user.
   */
  public function getSearchKeys();

}
