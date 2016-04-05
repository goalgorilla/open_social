<?php

namespace Drupal\facets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * The facet entity.
 */
interface FacetInterface extends ConfigEntityInterface {

  /**
   * Sets the facet's widget plugin id.
   *
   * @param string $widget
   *   The widget plugin id.
   *
   * @return $this
   *   Returns self
   */
  public function setWidget($widget);

  /**
   * Returns the facet's widget plugin id.
   *
   * @return string
   *   The widget plugin id.
   */
  public function getWidget();

  /**
   * Returns field identifier.
   *
   * @return string
   *   The field identifier of this facet.
   */
  public function getFieldIdentifier();

  /**
   * Sets field identifier.
   *
   * @param string $field_identifier
   *   The field identifier of this facet.
   *
   * @return $this
   *   Returns self.
   */
  public function setFieldIdentifier($field_identifier);

  /**
   * Returns the field alias used to identify the facet in the url.
   *
   * @return string
   *   The field alias for the facet.
   */
  public function getFieldAlias();

  /**
   * Returns the field name of the facet as used in the index.
   *
   * @TODO: Check if fieldIdentifier can be used as well!
   *
   * @return string
   *   The name of the facet.
   */
  public function getName();

  /**
   * Returns the name of the facet for use in the URL.
   *
   * @return string
   *   The name of the facet for use in the URL.
   */
  public function getUrlAlias();

  /**
   * Sets the name of the facet for use in the URL.
   *
   * @param string $url_alias
   *   The name of the facet for use in the URL.
   */
  public function setUrlAlias($url_alias);

  /**
   * Sets an item with value to active.
   *
   * @param string $value
   *   An item that is active.
   */
  public function setActiveItem($value);

  /**
   * Returns all the active items in the facet.
   *
   * @return mixed
   *   An array containing all active items.
   */
  public function getActiveItems();

  /**
   * Checks if a value is active.
   *
   * @param string $value
   *   The value to be checked.
   *
   * @return bool
   *   Is an active value.
   */
  public function isActiveValue($value);

  /**
   * Returns the show_only_one_result option.
   *
   * @return bool
   *   Show only one result.
   */
  public function getShowOnlyOneResult();

  /**
   * Sets the show_only_one_result option.
   *
   * @param bool $show_only_one_result
   *   Show only one result.
   */
  public function setShowOnlyOneResult($show_only_one_result);

  /**
   * Returns the result for the facet.
   *
   * @return \Drupal\facets\Result\ResultInterface[] $results
   *   The results of the facet.
   */
  public function getResults();

  /**
   * Sets the results for the facet.
   *
   * @param \Drupal\facets\Result\ResultInterface[] $results
   *   The results of the facet.
   */
  public function setResults(array $results);

  /**
   * Sets an array of unfiltered results.
   *
   * These unfiltered results are used to set the correct count of the actual
   * facet results when using the OR query operator. They are not results value
   * objects like those in ::$results.
   *
   * @param array $all_results
   *   Unfiltered results.
   */
  public function setUnfilteredResults(array $all_results = []);

  /**
   * Returns an array of unfiltered results.
   *
   * @return array
   *   Unfiltered results.
   */
  public function getUnfilteredResults();

  /**
   * Returns the query type instance.
   *
   * @return string
   *   The query type plugin being used.
   */
  public function getQueryType();

  /**
   * Returns the query operator.
   *
   * @return string
   *   The query operator being used.
   */
  public function getQueryOperator();

  /**
   * Returns the value of the exclude boolean.
   *
   * This will return true when the current facet's value should be exclusive
   * from the search rather than inclusive.
   * When this returns TRUE, the operator will be "<>" instead of "=".
   *
   * @return bool
   *   A boolean flag indicating if search should exlude selected facets
   */
  public function getExclude();

  /**
   * Returns the plugin name for the url processor.
   *
   * @return string
   *   The id of the url processor.
   */
  public function getUrlProcessorName();

  /**
   * Sets a string representation of the Facet source plugin.
   *
   * This is usually the name of the Search-api view.
   *
   * @param string $facet_source_id
   *   The facet source id.
   *
   * @return $this
   *   Returns self.
   */
  public function setFacetSourceId($facet_source_id);

  /**
   * Sets the query operator.
   *
   * @param string $operator
   *   The query operator being used.
   */
  public function setQueryOperator($operator);

  /**
   * Sets the exclude.
   *
   * @param bool $exclude
   *   A boolean flag indicating if search should exclude selected facets.
   */
  public function setExclude($exclude);

  /**
   * Returns the Facet source id.
   *
   * @return string
   *   The id of the facet source.
   */
  public function getFacetSourceId();

  /**
   * Returns the plugin instance of a facet source.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginInterface
   *   The plugin instance for the facet source.
   */
  public function getFacetSource();

  /**
   * Returns the facet source configuration object.
   *
   * @return \Drupal\facets\FacetSourceInterface
   *   A facet source configuration object.
   */
  public function getFacetSourceConfig();

  /**
   * Loads the facet sources for this facet.
   *
   * @param bool|TRUE $only_enabled
   *   Only return enabled facet sources.
   *
   * @return \Drupal\facets\FacetSource\FacetSourcePluginInterface[]
   *   An array of facet sources.
   */
  public function getFacetSources($only_enabled = TRUE);

  /**
   * Returns an array of processors with their configuration.
   *
   * @param bool|TRUE $only_enabled
   *   Only return enabled processors.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   An array of processors.
   */
  public function getProcessors($only_enabled = TRUE);

  /**
   * Loads this facets processors for a specific stage.
   *
   * @param string $stage
   *   The stage for which to return the processors. One of the
   *   \Drupal\facets\Processor\ProcessorInterface::STAGE_* constants.
   * @param bool $only_enabled
   *   (optional) If FALSE, also include disabled processors. Otherwise, only
   *   load enabled ones.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   An array of all enabled (or available, if if $only_enabled is FALSE)
   *   processors that support the given stage, ordered by the weight for that
   *   stage.
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE);

  /**
   * Retrieves this facets's processor configs.
   *
   * @return array
   *   An array of processors and their configs.
   */
  public function getProcessorConfigs();

  /**
   * Sets the "only visible when facet source is visible" boolean flag.
   *
   * @param bool $only_visible_when_facet_source_is_visible
   *   A boolean flag indicating if the facet should be hidden on a page that
   *   does not show the facet source.
   *
   * @return $this
   *   Returns self.
   */
  public function setOnlyVisibleWhenFacetSourceIsVisible($only_visible_when_facet_source_is_visible);

  /**
   * Returns the "only visible when facet source is visible" boolean flag.
   *
   * @return bool
   *   True when the facet is only shown on a page with the facet source.
   */
  public function getOnlyVisibleWhenFacetSourceIsVisible();

  /**
   * Adds a processor for this facet.
   *
   * @param array $processor
   *   An array definition for a processor.
   */
  public function addProcessor(array $processor);

  /**
   * Removes a processor for this facet.
   *
   * @param string $processor_id
   *   The plugin id of the processor.
   */
  public function removeProcessor($processor_id);

  /**
   * Defines the no-results behavior.
   *
   * @param array $behavior
   *   The definition of the behavior.
   */
  public function setEmptyBehavior(array $behavior);

  /**
   * Returns the defined no-results behavior or NULL if none defined.
   *
   * @return array|NULL
   *   The behavior definition or NULL.
   */
  public function getEmptyBehavior();

  /**
   * Returns the configuration of the selected widget.
   *
   * @return array
   *   The configuration settings for the widget.
   */
  public function getWidgetConfigs();

  /**
   * Sets the configuration for the widget of this facet.
   *
   * @param array $widget_config
   *   The configuration settings for the widget.
   */
  public function setWidgetConfigs(array $widget_config);

  /**
   * Returns any additional configuration for this facet, not defined above.
   *
   * @return array
   *   An array of additional configuration for the facet.
   */
  public function getFacetConfigs();

  /**
   * Defines any additional configuration for this facet not defined above.
   *
   * @param array $facet_config
   *   An array of additional configuration for the facet.
   */
  public function setFacetConfigs(array $facet_config);

  /**
   * Returns the weight of the facet.
   */
  public function getWeight();

  /**
   * Sets the weight of the facet.
   *
   * @param int $weight
   *   Weight of the facet.
   */
  public function setWeight($weight);

}
