<?php

namespace Drupal\facets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\facets\FacetInterface;

/**
 * Defines the facet configuration entity.
 *
 * @ConfigEntityType(
 *   id = "facets_facet",
 *   label = @Translation("Facet"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\facets\FacetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facets\Form\FacetForm",
 *       "edit" = "Drupal\facets\Form\FacetForm",
 *       "display" = "Drupal\facets\Form\FacetDisplayForm",
 *       "delete" = "Drupal\facets\Form\FacetDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer facets",
 *   config_prefix = "facet",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "url_alias",
 *     "weight",
 *     "show_only_one_result",
 *     "field_identifier",
 *     "query_type_name",
 *     "facet_source_id",
 *     "widget",
 *     "widget_configs",
 *     "query_operator",
 *     "exclude",
 *     "only_visible_when_facet_source_is_visible",
 *     "processor_configs",
 *     "empty_behavior",
 *     "facet_configs",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/facets",
 *     "add-form" = "/admin/config/search/facets/add-facet",
 *     "edit-form" = "/admin/config/search/facets/{facets_facet}/edit",
 *     "display-form" = "/admin/config/search/facets/{facets_facet}/display",
 *     "delete-form" = "/admin/config/search/facets/{facets_facet}/delete",
 *   }
 * )
 */
class Facet extends ConfigEntityBase implements FacetInterface {

  /**
   * The ID of the facet.
   *
   * @var string
   */
  protected $id;

  /**
   * A name to be displayed for the facet.
   *
   * @var string
   */
  protected $name;

  /**
   * The name for the parameter when used in the URL.
   *
   * @var string
   */
  protected $url_alias;

  /**
   * A string describing the facet.
   *
   * @var string
   */
  protected $description;

  /**
   * The plugin name of the widget.
   *
   * @var string
   */
  protected $widget;

  /**
   * Configuration for the widget. This is a key-value stored array.
   *
   * @var array
   */
  protected $widget_configs = [];

  /**
   * The operator to hand over to the query, currently AND | OR.
   *
   * @var string
   */
  protected $query_operator;

  /**
   * A boolean flag indicating if search should exclude selected facets.
   *
   * @var bool
   */
  protected $exclude;

  /**
   * The field identifier.
   *
   * @var string
   */
  protected $field_identifier;

  /**
   * The query type name.
   *
   * @var string
   */
  protected $query_type_name;

  /**
   * The plugin name of the url processor.
   *
   * @var string
   */
  protected $url_processor_name;

  /**
   * The id of the facet source.
   *
   * @var string
   */
  protected $facet_source_id;

  /**
   * The facet source belonging to this facet.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginInterface
   *
   * @see getFacetSource()
   */
  protected $facet_source_instance;

  /**
   * The path all the links should point to.
   *
   * @var string
   */
  protected $path;

  /**
   * The results.
   *
   * @var \Drupal\facets\Result\ResultInterface[]
   */
  protected $results = [];

  /**
   * The results.
   *
   * @var \Drupal\facets\Result\ResultInterface[]
   */
  protected $unfiltered_results = [];

  /**
   * An array of active values.
   *
   * @var string[]
   */
  protected $active_values = [];

  /**
   * An array containing the facet source plugins.
   *
   * @var array
   */
  protected $facetSourcePlugins;

  /**
   * Cached information about the processors available for this facet.
   *
   * @var \Drupal\facets\Processor\ProcessorInterface[]|null
   *
   * @see loadProcessors()
   */
  protected $processors;

  /**
   * Configuration for the processors. This is an array of arrays.
   *
   * @var array
   */
  protected $processor_configs = [];

  /**
   * Additional facet configurations.
   *
   * @var array
   */
  protected $facet_configs = [];

  /**
   * Is the facet only visible when the facet source is only visible.
   *
   * A boolean that defines whether or not the facet is only visible when the
   * facet source is visible.
   *
   * @var boolean
   */
  protected $only_visible_when_facet_source_is_visible;

  /**
   * Determines if only one result can be selected in the facet at one time.
   *
   * @var bool
   */
  protected $show_only_one_result = FALSE;

  /**
   * The no-result configuration.
   *
   * @var string[];
   */
  protected $empty_behavior;

  /**
   * The widget plugin manager.
   *
   * @var object
   */
  protected $widget_plugin_manager;

  /**
   * The facet source config object.
   *
   * @var \Drupal\Facets\FacetSourceInterface
   *   The facet source config object.
   */
  protected $facetSourceConfig;

  /**
   * The facet weight.
   *
   * @var integer
   *   The weight of the facet.
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
  }

  /**
   * Returns the widget plugin manager.
   *
   * @return \Drupal\facets\Widget\WidgetPluginManager
   *   The widget plugin manager.
   */
  public function getWidgetManager() {
    $container = \Drupal::getContainer();

    return $this->widget_plugin_manager ?: $container->get('plugin.manager.facets.widget');
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidget($widget) {
    $this->widget = $widget;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypes() {
    return $this->query_type_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return $this->widget;
  }

  /**
   * Retrieves all processors supported by this facet.
   *
   * @return \Drupal\facets\Processor\ProcessorInterface[]
   *   The loaded processors, keyed by processor ID.
   */
  protected function loadProcessors() {
    if (!isset($this->processors)) {
      /* @var $processor_plugin_manager \Drupal\facets\Processor\ProcessorPluginManager */
      $processor_plugin_manager = \Drupal::service('plugin.manager.facets.processor');
      $processor_settings = $this->getProcessorConfigs();

      foreach ($processor_plugin_manager->getDefinitions() as $name => $processor_definition) {
        if (class_exists($processor_definition['class']) && empty($this->processors[$name])) {
          // Create our settings for this processor.
          $settings = empty($processor_settings[$name]['settings']) ? [] : $processor_settings[$name]['settings'];
          $settings['facet'] = $this;

          /* @var $processor \Drupal\facets\Processor\ProcessorInterface */
          $processor = $processor_plugin_manager->createInstance($name, $settings);
          $this->processors[$name] = $processor;
        }
        elseif (!class_exists($processor_definition['class'])) {
          \Drupal::logger('facets')
            ->warning('Processor @id specifies a non-existing @class.', array(
              '@id' => $name,
              '@class' => $processor_definition['class'],
            ));
        }
      }
    }

    return $this->processors;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorConfigs() {
    return !empty($this->processor_configs) ? $this->processor_configs : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    $facet_source = $this->getFacetSource();
    $query_types = $facet_source->getQueryTypesForFacet($this);

    // Get our widget configured for this facet.
    /** @var \Drupal\facets\Widget\WidgetInterface $widget */
    $widget = $this->getWidgetManager()->createInstance($this->getWidget());
    // Give the widget the chance to select a preferred query type. This is
    // useful with a date widget, as it needs to select the date query type.
    return $widget->getQueryType($query_types);
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryOperator($operator = '') {
    return $this->query_operator = $operator;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryOperator() {
    return $this->query_operator ?: 'OR';
  }

  /**
   * {@inheritdoc}
   */
  public function setExclude($exclude) {
    return $this->exclude = $exclude;
  }

  /**
   * {@inheritdoc}
   */
  public function getExclude() {
    return $this->exclude;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldAlias() {
    // For now, create the field alias based on the field identifier.
    $field_alias = preg_replace('/[:\/]+/', '_', $this->field_identifier);
    return $field_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveItem($value) {
    if (!in_array($value, $this->active_values)) {
      $this->active_values[] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveItems() {
    return $this->active_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldIdentifier() {
    return $this->field_identifier;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldIdentifier($field_identifier) {
    $this->field_identifier = $field_identifier;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlProcessorName() {
    // @Todo: for now if the url processor is not set, defualt to query_string.
    return isset($this->url_processor_name) ? $this->url_processor_name : 'query_string';
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlAlias() {
    return $this->url_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrlAlias($url_alias) {
    $this->url_alias = $url_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacetSourceId($facet_source_id) {
    $this->facet_source_id = $facet_source_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSource() {

    if (!$this->facet_source_instance && $this->facet_source_id) {
      /* @var $facet_source_plugin_manager \Drupal\facets\FacetSource\FacetSourcePluginManager */
      $facet_source_plugin_manager = \Drupal::service('plugin.manager.facets.facet_source');
      $this->facet_source_instance = $facet_source_plugin_manager->createInstance($this->facet_source_id, ['facet' => $this]);
    }

    return $this->facet_source_instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getShowOnlyOneResult() {
    return $this->show_only_one_result;
  }

  /**
   * {@inheritdoc}
   */
  public function setShowOnlyOneResult($show_only_one_result) {
    $this->show_only_one_result = $show_only_one_result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSourceId() {
    return $this->facet_source_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSourceConfig() {
    // Return the facet source config object, if it's already set on the facet.
    if ($this->facetSourceConfig instanceof FacetSource) {
      return $this->facetSourceConfig;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('facets_facet_source');
    $source_id = str_replace(':', '__', $this->facet_source_id);

    // Load and return the facet source config object from the storage.
    $facet_source = $storage->load($source_id);
    if ($facet_source instanceof FacetSource) {
      $this->facetSourceConfig = $facet_source;
      return $this->facetSourceConfig;
    }

    // We didn't have a facet source config entity yet for this facet source
    // plugin, so we create it on the fly.
    $facet_source = new FacetSource(
      [
        'id' => $source_id,
        'name' => $this->facet_source_id,
      ],
      'facets_facet_source'
    );
    $facet_source->save();

    $this->facetSourceConfig = $facet_source;
    return $this->facetSourceConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function setResults(array $results) {
    $this->results = $results;
    // If there are active values,
    // set the results which are active to active.
    if (count($this->active_values)) {
      foreach ($this->results as $result) {
        if (in_array($result->getRawValue(), $this->active_values)) {
          $result->setActiveState(TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUnfilteredResults(array $all_results = []) {
    $this->unfiltered_results = $all_results;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnfilteredResults() {
    return $this->unfiltered_results;
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveValue($value) {
    $is_active = FALSE;
    if (in_array($value, $this->active_values)) {
      $is_active = TRUE;
    }
    return $is_active;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetSources($only_enabled = FALSE) {
    if (!isset($this->facetSourcePlugins)) {
      $this->facetSourcePlugins = [];

      /* @var $facet_source_plugin_manager \Drupal\facets\FacetSource\FacetSourcePluginManager */
      $facet_source_plugin_manager = \Drupal::service('plugin.manager.facets.facet_source');

      foreach ($facet_source_plugin_manager->getDefinitions() as $name => $facet_source_definition) {
        if (class_exists($facet_source_definition['class']) && empty($this->facetSourcePlugins[$name])) {
          // Create our settings for this facet source..
          $config = isset($this->facetSourcePlugins[$name]) ? $this->facetSourcePlugins[$name] : [];

          /* @var $facet_source \Drupal\facets\FacetSource\FacetSourcePluginInterface */
          $facet_source = $facet_source_plugin_manager->createInstance($name, $config);
          $this->facetSourcePlugins[$name] = $facet_source;
        }
        elseif (!class_exists($facet_source_definition['class'])) {
          \Drupal::logger('facets')
            ->warning('Facet Source @id specifies a non-existing @class.', [
              '@id' => $name,
              '@class' => $facet_source_definition['class'],
            ]);
        }
      }
    }

    // Filter facet sources by status if required.
    if (!$only_enabled) {
      return $this->facetSourcePlugins;
    }

    return array_intersect_key($this->facetSourcePlugins, array_flip($this->facetSourcePlugins));
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors($only_enabled = TRUE) {
    $processors = $this->loadProcessors();

    // Filter processors by status if required. Enabled processors are those
    // which have settings in the processor_configs.
    if ($only_enabled) {
      $processors_settings = $this->getProcessorConfigs();
      $processors = array_intersect_key($processors, $processors_settings);
    }

    return $processors;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE) {
    $processors = $this->loadProcessors();
    $processor_settings = $this->getProcessorConfigs();
    $processor_weights = array();

    // Get a list of all processors meeting the criteria (stage and, optionally,
    // enabled) along with their effective weights (user-set or default).
    foreach ($processors as $name => $processor) {
      if ($processor->supportsStage($stage) && !($only_enabled && empty($processor_settings[$name]))) {
        if (!empty($processor_settings[$name]['weights'][$stage])) {
          $processor_weights[$name] = $processor_settings[$name]['weights'][$stage];
        }
        else {
          $processor_weights[$name] = $processor->getDefaultWeight($stage);
        }
      }
    }

    // Sort requested processors by weight.
    asort($processor_weights);

    $return_processors = array();
    foreach ($processor_weights as $name => $weight) {
      $return_processors[$name] = $processors[$name];
    }
    return $return_processors;
  }

  /**
   * {@inheritdoc}
   */
  public function setOnlyVisibleWhenFacetSourceIsVisible($only_visible_when_facet_source_is_visible) {
    $this->only_visible_when_facet_source_is_visible = $only_visible_when_facet_source_is_visible;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnlyVisibleWhenFacetSourceIsVisible() {
    return $this->only_visible_when_facet_source_is_visible;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(array $processor) {
    $this->processor_configs[$processor['processor_id']] = [
      'processor_id' => $processor['processor_id'],
      'weights' => $processor['weights'],
      'settings' => $processor['settings'],
    ];
    // Sort the processors so we won't have unnecessary changes.
    ksort($this->processor_configs);
  }

  /**
   * {@inheritdoc}
   */
  public function removeProcessor($processor_id) {
    unset($this->processor_configs[$processor_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyBehavior() {
    return $this->empty_behavior;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmptyBehavior(array $empty_behavior) {
    $this->empty_behavior = $empty_behavior;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidgetConfigs(array $widget_configs) {
    $this->widget_configs = $widget_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetConfigs() {
    return $this->widget_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function setFacetConfigs(array $facet_configs) {
    $this->facet_configs = $facet_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetConfigs() {
    return $this->facet_configs;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

}
