<?php

namespace Drupal\facets\FacetManager;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Drupal\facets\Widget\WidgetPluginManager;

/**
 * The facet manager.
 *
 * The manager is responsible for interactions with the Search backend, such as
 * altering the query, it is also responsible for executing and building the
 * facet. It is also responsible for running the processors.
 */
class DefaultFacetManager {

  use StringTranslationTrait;

  /**
   * The query type plugin manager.
   *
   * @var \Drupal\facets\QueryType\QueryTypePluginManager
   *   The query type plugin manager.
   */
  protected $queryTypePluginManager;

  /**
   * The facet source plugin manager.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  protected $processorPluginManager;

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\facets\Widget\WidgetPluginManager
   */
  protected $widgetPluginManager;

  /**
   * An array of facets that are being rendered.
   *
   * @var \Drupal\facets\FacetInterface[]
   *
   * @see \Drupal\facets\FacetInterface
   * @see \Drupal\facets\Entity\Facet
   */
  protected $facets = [];

  /**
   * An array flagging which facet source' facets have been processed.
   *
   * This variable acts as a semaphore that ensures facet data is processed
   * only once.
   *
   * @var bool[]
   *
   * @see \Drupal\facets\FacetsFacetManager::processFacets()
   */
  protected $processedFacetSources = [];

  /**
   * Stores the search path associated with this searcher.
   *
   * @var string
   */
  protected $searchPath;

  /**
   * Stores settings with defaults.
   *
   * @var array
   *
   * @see \Drupal\facets\FacetsFacetManager::getFacetSettings()
   */
  protected $settings = [];

  /**
   * The entity storage for facets.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|object
   */
  protected $facetStorage;

  /**
   * Constructs a new instance of the DefaultFacetManager.
   *
   * @param \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager
   *   The query type plugin manager.
   * @param \Drupal\facets\Widget\WidgetPluginManager $widget_plugin_manager
   *   The widget plugin manager.
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facet_source_manager
   *   The facet source plugin manager.
   * @param \Drupal\facets\Processor\ProcessorPluginManager $processor_plugin_manager
   *   The processor plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type plugin manager.
   */
  public function __construct(QueryTypePluginManager $query_type_plugin_manager, WidgetPluginManager $widget_plugin_manager, FacetSourcePluginManager $facet_source_manager, ProcessorPluginManager $processor_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->queryTypePluginManager = $query_type_plugin_manager;
    $this->widgetPluginManager = $widget_plugin_manager;
    $this->facetSourcePluginManager = $facet_source_manager;
    $this->processorPluginManager = $processor_plugin_manager;
    $this->facetStorage = $entity_type_manager->getStorage('facets_facet');

    // Immediately initialize the facets. This can be done directly because the
    // only thing needed is the url.
    $this->initFacets();
  }

  /**
   * Allows the backend to add facet queries to its native query object.
   *
   * This method is called by the implementing module to initialize the facet
   * display process.
   *
   * @param mixed $query
   *   The backend's native query object.
   * @param string $facetsource_id
   *   The facet source ID to process.
   */
  public function alterQuery(&$query, $facetsource_id) {
    /** @var \Drupal\facets\FacetInterface[] $facets */
    foreach ($this->getFacetsByFacetSourceId($facetsource_id) as $facet) {
      /** @var \Drupal\facets\QueryType\QueryTypeInterface $query_type_plugin */
      $query_type_plugin = $this->queryTypePluginManager->createInstance($facet->getQueryType(), ['query' => $query, 'facet' => $facet]);
      $unfiltered_results = $query_type_plugin->execute();

      // Save unfiltered results in facet.
      if (!is_null($unfiltered_results)) {
        $facet->setUnfilteredResults($unfiltered_results);
      }
    }
  }

  /**
   * Returns enabled facets for the searcher associated with this FacetManager.
   *
   * @return \Drupal\facets\FacetInterface[]
   *   An array of enabled facets.
   */
  public function getEnabledFacets() {
    return $this->facetStorage->loadMultiple();
  }

  /**
   * Returns currently rendered facets filtered by facetsource ID.
   *
   * @param string $facetsource_id
   *   The facetsource ID to filter by.
   *
   * @return \Drupal\facets\FacetInterface[]
   *   An array of enabled facets.
   */
  protected function getFacetsByFacetSourceId($facetsource_id) {
    $facets = [];
    foreach ($this->facets as $facet) {
      if ($facet->getFacetSourceId() == $facetsource_id) {
        $facets[] = $facet;
      }
    }
    return $facets;
  }

  /**
   * Initializes facet builds, sets the breadcrumb trail.
   *
   * Facets are built via FacetsFacetProcessor objects. Facets only need to be
   * processed, or built, once The FacetsFacetManager::processed semaphore is
   * set when this method is called ensuring that facets are built only once
   * regardless of how many times this method is called.
   *
   * @param string|NULL $facetsource_id
   *   The facetsource if of the currently processed facet.
   */
  public function processFacets($facetsource_id = NULL) {
    if (empty($facetsource_id)) {
      foreach ($this->facets as $facet) {
        $current_facetsource_id = $facet->getFacetSourceId();
        $this->processFacets($current_facetsource_id);
      }
    }
    elseif (empty($this->processedFacetSources[$facetsource_id])) {
      // First add the results to the facets.
      $this->updateResults($facetsource_id);

      $this->processedFacetSources[$facetsource_id] = TRUE;
    }
  }

  /**
   * Initializes enabled facets.
   *
   * In this method all pre-query processors get called and their contents are
   * executed.
   */
  protected function initFacets() {
    if (empty($this->facets)) {
      $this->facets = $this->getEnabledFacets();
      foreach ($this->facets as $facet) {

        foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY) as $processor) {
          /** @var PreQueryProcessorInterface $pre_query_processor */
          $pre_query_processor = $this->processorPluginManager->createInstance($processor->getPluginDefinition()['id'], ['facet' => $facet]);
          if (!$pre_query_processor instanceof PreQueryProcessorInterface) {
            throw new InvalidProcessorException(new FormattableMarkup("The processor @processor has a pre_query definition but doesn't implement the required PreQueryProcessorInterface interface", ['@processor' => $processor->getPluginDefinition()['id']]));
          }
          $pre_query_processor->preQuery($facet);
        }
      }
    }
  }

  /**
   * Builds a facet and returns it as a renderable array.
   *
   * This method delegates to the relevant plugins to render a facet, it calls
   * out to a widget plugin to do the actual rendering when results are found.
   * When no results are found it calls out to the correct empty result plugin
   * to build a render array.
   *
   * Before doing any rendering, the processors that implement the
   * BuildProcessorInterface enabled on this facet will run.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet we should build.
   *
   * @return array
   *   Facet render arrays.
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   *   Throws an exception when an invalid processor is linked to the facet.
   */
  public function build(FacetInterface $facet) {
    // It might be that the facet received here, is not the same as the already
    // loaded facets in the FacetManager.
    // For that reason, get the facet from the already loaded facets in the
    // FacetManager.
    $facet = $this->facets[$facet->id()];
    $facet_source_id = $facet->getFacetSourceId();

    if ($facet->getOnlyVisibleWhenFacetSourceIsVisible()) {
      // Block rendering and processing should be stopped when the facet source
      // is not available on the page. Returning an empty array here is enough
      // to halt all further processing.
      $facet_source = $facet->getFacetSource();
      if (!$facet_source->isRenderedInCurrentRequest()) {
        return [];
      }
    }

    // For clarity, process facets is called each build.
    // The first facet therefor will trigger the processing. Note that
    // processing is done only once, so repeatedly calling this method will not
    // trigger the processing more than once.
    $this->processFacets($facet_source_id);

    // Get the current results from the facets and let all processors that
    // trigger on the build step do their build processing.
    // @see \Drupal\facets\Processor\BuildProcessorInterface.
    // @see \Drupal\facets\Processor\WidgetOrderProcessorInterface.
    $results = $facet->getResults();

    foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_BUILD) as $processor) {
      /** @var \Drupal\facets\Processor\BuildProcessorInterface $build_processor */
      $build_processor = $this->processorPluginManager->createInstance($processor->getPluginDefinition()['id'], ['facet' => $facet]);
      if (!$build_processor instanceof BuildProcessorInterface) {
        throw new InvalidProcessorException(new FormattableMarkup("The processor @processor has a build definition but doesn't implement the required BuildProcessorInterface interface", ['@processor' => $processor['processor_id']]));
      }
      $results = $build_processor->build($facet, $results);
    }
    $facet->setResults($results);

    // No results behavior handling. Return a custom text or false depending on
    // settings.
    if (empty($facet->getResults())) {
      $empty_behavior = $facet->getEmptyBehavior();
      if ($empty_behavior['behavior'] == 'text') {
        return ['#markup' => $empty_behavior['text']];
      }
      else {
        return [];
      }
    }

    // Let the widget plugin render the facet.
    /** @var \Drupal\facets\Widget\WidgetInterface $widget */
    $widget = $this->widgetPluginManager->createInstance($facet->getWidget());

    return $widget->build($facet);
  }

  /**
   * Updates all facets of a given facet source with the results.
   *
   * @param string $facetsource_id
   *   The facet source ID of the currently processed facet.
   */
  public function updateResults($facetsource_id) {
    $facets = $this->getFacetsByFacetSourceId($facetsource_id);
    if ($facets) {
      /** @var \drupal\facets\FacetSource\FacetSourcePluginInterface $facet_source_plugin */
      $facet_source_plugin = $this->facetSourcePluginManager->createInstance($facetsource_id);

      $facet_source_plugin->fillFacetsWithResults($facets);
    }
  }

  /**
   * Returns one of the processed facets.
   *
   * Returns one of the processed facets, this is a facet with filled results.
   * Keep in mind that if you want to have the facet's build processor executed,
   * there needs to be an extra call to the FacetManager::build with the facet
   * returned here as argument.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to process.
   *
   * @return \Drupal\facets\FacetInterface|NULL
   *   The updated facet if it exists, NULL otherwise.
   */
  public function returnProcessedFacet(FacetInterface $facet) {
    $this->processFacets($facet->getFacetSourceId());
    return !empty($this->facets[$facet->id()]) ? $this->facets[$facet->id()] : NULL;
  }

}
