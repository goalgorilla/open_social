<?php

namespace Drupal\facets\Plugin\facets\facet_source;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\facets\FacetSource\SearchApiFacetSourceInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * A facet source to support search api views.
 *
 * This facet source only supports views that have a search api index as a base,
 * and only those displays that are a block or a page.
 *
 * @FacetsFacetSource(
 *   id = "search_api_views",
 *   deriver = "Drupal\facets\Plugin\facets\facet_source\SearchApiViewsDeriver"
 * )
 */
class SearchApiViews extends SearchApiBaseFacetSource implements SearchApiFacetSourceInterface {

  use DependencySerializationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager|null
   */
  protected $entityTypeManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|null
   */
  protected $typedDataManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The search index the query should is executed on.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $query_type_plugin_manager, $search_results_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $search_results_cache);

    // Load facet plugin definition and depending on those settings; load the
    // corresponding view with the correct view with the correct display set.
    // Get that display's query so we can check if this is a Search API based
    // view.
    $view = Views::getView($plugin_definition['view_id']);
    if (!empty($view)) {
      $view->setDisplay($plugin_definition['view_display']);
      $query = $view->getQuery();

      // Only add the index if the $query is a Search API Query.
      if ($query instanceof SearchApiQuery) {
        // Set the Search API Index.
        $this->index = $query->getIndex();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $display = View::load($this->pluginDefinition['view_id'])->getDisplay($this->pluginDefinition['view_display']);
    switch ($display['display_plugin']) {
      case 'page':
        $view = Views::getView($this->pluginDefinition['view_id']);
        $view->setDisplay($this->pluginDefinition['view_display']);
        return '/' . $view->getDisplay()->getPath();

      case 'block':
      default:
        return \Drupal::service('path.current')->getPath();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults($facets) {
    // Check if there are results in the static cache.
    $results = $this->searchApiResultsCache->getResults($this->pluginId);

    // If our results are not there, execute the view to get the results.
    if (!$results) {
      // If there are no results, execute the view. and check for results again!
      $view = Views::getView($this->pluginDefinition['view_id']);
      $view->setDisplay($this->pluginDefinition['view_display']);
      $view->execute();
      $results = $this->searchApiResultsCache->getResults($this->pluginId);
    }

    // Get the results from the cache. It is possible it still errored out.
    // @todo figure out what to do when this errors out.
    if ($results instanceof ResultSetInterface) {
      // Get our facet data.
      $facet_results = $results->getExtraData('search_api_facets');

      // Loop over each facet and execute the build method from the given
      // query type.
      foreach ($facets as $facet) {
        $configuration = array(
          'query' => NULL,
          'facet' => $facet,
          'results' => isset($facet_results[$facet->getFieldIdentifier()]) ? $facet_results[$facet->getFieldIdentifier()] : [],
        );

        // Get the Facet Specific Query Type so we can process the results
        // using the build() function of the query type.
        $query_type = $this->queryTypePluginManager->createInstance($facet->getQueryType(), $configuration);
        $query_type->build();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    $display = View::load($this->pluginDefinition['view_id'])->getDisplay($this->pluginDefinition['view_display']);
    switch ($display['display_plugin']) {
      case 'page':
        $request = \Drupal::requestStack()->getMasterRequest();
        if ($request->attributes->get('_controller') === 'Drupal\views\Routing\ViewPageController::handle') {
          list(, $search_api_view_id, $search_api_view_display) = explode(':', $this->getPluginId());

          if ($request->attributes->get('view_id') == $search_api_view_id && $request->attributes->get('display_id') == $search_api_view_display) {
            return TRUE;
          }
        }
        return FALSE;

      case 'block':
        // There is no way to know if a block is embedded on a page, because
        // blocks can be rendered in isolation (see big_pipe, esi, ...). To be
        // sure we're not disclosing information we're not sure about, we always
        // return false.
        return FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

}
