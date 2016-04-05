<?php

namespace Drupal\facets_query_processor\Plugin\facets\url_processor;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\UrlProcessor\UrlProcessorPluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Query string URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "dummy_query",
 *   label = @Translation("Dummy query"),
 *   description = @Translation("Dummy for testing.")
 * )
 */
class DummyQuery extends UrlProcessorPluginBase {

  /**
   * A string that separates the filters in the query string.
   */
  const SEPARATOR = '||';

  /**
   * A string of how to represent the facet in the url.
   *
   * @var string
   */
  protected $urlAlias;

  /**
   * An array of active filters.
   *
   * @var string[]
   *   An array containing the active filters
   */
  protected $activeFilters = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $request);
    $this->initializeActiveFilters();
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {
    // Create links for all the values.
    // First get the current list of get parameters.
    $get_params = $this->request->query;

    // Set the url alias from the the facet object.
    $this->urlAlias = $facet->getUrlAlias();

    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }

    /** @var \Drupal\facets\Result\ResultInterface $result */
    foreach ($results as &$result) {
      $filter_string = $this->urlAlias . self::SEPARATOR . $result->getRawValue();
      $result_get_params = clone $get_params;

      $filter_params = $result_get_params->get($this->filterKey, [], TRUE);
      // If the value is active, remove the filter string from the parameters.
      if ($result->isActive()) {
        foreach ($filter_params as $key => $filter_param) {
          if ($filter_param == $filter_string) {
            unset($filter_params[$key]);
          }
        }
      }
      // If the value is not active, add the filter string.
      else {
        $filter_params[] = $filter_string;
      }

      $result_get_params->set($this->filterKey, $filter_params);
      $request = $this->request;
      if ($facet->getFacetSource()->getPath()) {
        $request = Request::create($facet->getFacetSource()->getPath());
      }
      $url = Url::createFromRequest($request);
      $url->setOption('query', $result_get_params->all());

      $result->setUrl($url);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItems(FacetInterface $facet) {
    // Set the url alias from the the facet object.
    $this->urlAlias = $facet->getUrlAlias();

    // Get the filter key of the facet.
    if (isset($this->activeFilters[$this->urlAlias])) {
      foreach ($this->activeFilters[$this->urlAlias] as $value) {
        $facet->setActiveItem(trim($value, '"'));
      }
    }
  }

  /**
   * Initializes the active filters.
   *
   * Get all the filters that are active. This method only get's all the
   * filters but doesn't assign them to facets. In the processFacet method the
   * active values for a specific facet are added to the facet.
   */
  protected function initializeActiveFilters() {
    $url_parameters = $this->request->query;

    // Get the active facet parameters.
    $active_params = $url_parameters->get($this->filterKey, array(), TRUE);

    // Explode the active params on the separator.
    foreach ($active_params as $param) {
      list($key, $value) = explode(self::SEPARATOR, $param);
      if (!isset($this->activeFilters[$key])) {
        $this->activeFilters[$key] = [$value];
      }
      else {
        $this->activeFilters[$key][] = $value;
      }
    }
  }

}
