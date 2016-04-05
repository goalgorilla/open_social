<?php

namespace Drupal\facets\Plugin\facets\facet_source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\Exception\InvalidQueryTypeException;
use Drupal\facets\FacetInterface;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\facets\FacetSource\FacetSourcePluginBase;
use Drupal\search_api\FacetsQueryTypeMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for Search API facet sources.
 */
abstract class SearchApiBaseFacetSource extends FacetSourcePluginBase {

  use StringTranslationTrait;

  /**
   * The search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The search result cache.
   *
   * @var \Drupal\search_api\Query\ResultsCacheInterface
   */
  protected $searchApiResultsCache;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, $query_type_plugin_manager, $search_results_cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager);
    // Since defaultConfiguration() depends on the plugin definition, we need to
    // override the constructor and set the definition property before calling
    // that method.
    $this->pluginDefinition = $plugin_definition;
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration;
    $this->searchApiResultsCache = $search_results_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager */
    $query_type_plugin_manager = $container->get('plugin.manager.facets.query_type');

    /** @var \Drupal\search_api\Query\ResultsCacheInterface $results_cache */
    $search_results_cache = $container->get('search_api.results_static_cache');
    return new static($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager, $search_results_cache);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['field_identifier'] = [
      '#type' => 'select',
      '#options' => $this->getFields(),
      '#title' => $this->t('Facet field'),
      '#description' => $this->t('Choose the indexed field.'),
      '#required' => TRUE,
      '#default_value' => $this->facet->getFieldIdentifier(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $indexed_fields = [];
    $fields = $this->index->getFields();
    foreach ($fields as $field) {
      $indexed_fields[$field->getFieldIdentifier()] = $field->getLabel();
    }
    return $indexed_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    // Get our Facets Field Identifier, which is equal to the Search API Field
    // identifier.
    $field_id = $facet->getFieldIdentifier();
    // Get the Search API Server.
    $server = $this->index->getServerInstance();
    // Get the Search API Backend.
    $backend = $server->getBackend();

    $fields = $this->index->getFields();
    foreach ($fields as $field) {
      if ($field->getFieldIdentifier() == $field_id) {
        return $this->getQueryTypesForDataType($backend, $field->getType());
      }
    }

    throw new InvalidQueryTypeException($this->t("No available query types were found for facet @facet", ['@facet' => $facet->getName()]));
  }

  /**
   * Retrieves the query types for a specified data type.
   *
   * Backend plugins can use this method to override the default query types
   * provided by the Search API with backend-specific ones that better use
   * features of that backend.
   *
   * @param \Drupal\search_api\Backend\BackendInterface $backend
   *   The backend that we want to get the query types for.
   * @param string $data_type_plugin_id
   *   The identifier of the data type.
   *
   * @return string[]
   *   An associative array with the plugin IDs of allowed query types, keyed by
   *   the generic name of the query_type.
   *
   * @see hook_facets_search_api_query_type_mapping_alter()
   */
  public function getQueryTypesForDataType(BackendInterface $backend, $data_type_plugin_id) {
    $query_types = [];
    // @todo Make this flexible for each data type in Search API.
    switch ($data_type_plugin_id) {
      case 'boolean':
      case 'date':
      case 'decimal':
      case 'integer':
      case 'string':
      case 'text':
        $query_types['string'] = 'search_api_string';
        break;
    }

    // Find out if the backend implemented the Interface to retrieve specific
    // query types for the supported data_types.
    if ($backend instanceof FacetsQueryTypeMappingInterface) {
      // If the input arrays have the same string keys, then the later value
      // for that key will overwrite the previous one. If, however, the arrays
      // contain numeric keys, the later value will not overwrite the original
      // value, but will be appended.
      $query_types = array_merge($query_types, $backend->getQueryTypesForDataType($data_type_plugin_id));
    }
    // Add it to a variable so we can pass it by reference. Alter hook complains
    // due to the property of the backend object is not passable by reference.
    $backend_plugin_id = $backend->getPluginId();

    // Let modules alter this mapping.
    \Drupal::moduleHandler()->alter('facets_search_api_query_type_mapping', $backend_plugin_id, $query_types);

    return $query_types;
  }

}
