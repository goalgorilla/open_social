<?php

namespace Drupal\facets\Plugin\facets\url_processor;

use Drupal\Core\Url;
use Drupal\facets\FacetInterface;
use Drupal\facets\UrlProcessor\UrlProcessorPluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Query string URL processor.
 *
 * @FacetsUrlProcessor(
 *   id = "query_string",
 *   label = @Translation("Query string"),
 *   description = @Translation("Query string is the default Facets URL processor, and uses GET parameters, e.g. ?f[0]=brand:drupal&f[1]=color:blue")
 * )
 */
class QueryString extends UrlProcessorPluginBase {

  /**
   * A string that separates the filters in the query string.
   */
  const SEPARATOR = ':';

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
    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }

    // First get the current list of get parameters.
    $get_params = $this->request->query;

    // Set the url alias from the the facet object.
    $this->urlAlias = $facet->getUrlAlias();

    $request = $this->request;
    if ($facet->getFacetSource()->getPath()) {
      $request = Request::create($facet->getFacetSource()->getPath());
    }
    $url = Url::createFromRequest($request);
    $url->setOption('attributes', ['rel' => 'nofollow']);

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      // Flag if children filter params need to be removed.
      $remove_children = FALSE;
      // Sets the url for children.
      if ($children = $result->getChildren()) {
        $this->buildUrls($facet, $children);
      }

      $filter_string = $this->urlAlias . self::SEPARATOR . $result->getRawValue();
      $result_get_params = clone $get_params;

      $filter_params = $result_get_params->get($this->filterKey, [], TRUE);
      // If the value is active, remove the filter string from the parameters.
      if ($result->isActive()) {
        foreach ($filter_params as $key => $filter_param) {
          if ($filter_param == $filter_string) {
            $remove_children = TRUE;
            unset($filter_params[$key]);
          }
          elseif ($remove_children) {
            unset($filter_params[$key]);
          }
        }
      }
      // If the value is not active, add the filter string.
      else {
        $filter_params[] = $filter_string;
        // Exclude currently active results from the filter params if we are in
        // the show_only_one_result mode.
        if ($facet->getShowOnlyOneResult()) {
          foreach ($results as $result2) {
            if ($result2->isActive()) {
              $active_filter_string = $this->urlAlias . self::SEPARATOR . $result2->getRawValue();
              foreach ($filter_params as $key2 => $filter_param2) {
                if ($filter_param2 == $active_filter_string) {
                  unset($filter_params[$key2]);
                }
              }
            }
          }
        }
      }

      $result_get_params->set($this->filterKey, $filter_params);

      $url = clone $url;
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
      $explosion = explode(self::SEPARATOR, $param);
      $key = array_shift($explosion);
      $value = '';
      while (count($explosion) > 0) {
        $value .= array_shift($explosion);
        if (count($explosion) > 0) {
          $value .= self::SEPARATOR;
        }
      }
      if (!isset($this->activeFilters[$key])) {
        $this->activeFilters[$key] = [$value];
      }
      else {
        $this->activeFilters[$key][] = $value;
      }
    }
  }

}
