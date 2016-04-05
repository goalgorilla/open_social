<?php

namespace Drupal\core_search_facets\Plugin\facets\facet_source;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_search_facets\Plugin\CoreSearchFacetSourceInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetSource\FacetSourcePluginBase;
use Drupal\facets\QueryType\QueryTypePluginManager;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Represents a facet source which represents the Search API views.
 *
 * @FacetsFacetSource(
 *   id = "core_node_search",
 *   deriver = "Drupal\core_search_facets\Plugin\facets\facet_source\CoreNodeSearchFacetSourceDeriver"
 * )
 */
class CoreNodeSearchFacetSource extends FacetSourcePluginBase implements CoreSearchFacetSourceInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

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
   * The plugin manager for core search plugins.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * The facet query being executed.
   */
  protected $facetQueryExtender;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueryTypePluginManager $query_type_plugin_manager, SearchPluginManager $search_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager);
    $this->searchManager = $search_manager;
    $this->setSearchKeys($request_stack->getMasterRequest()->query->get('keys'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = $container->get('request_stack');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.facets.query_type'),
      $container->get('plugin.manager.search'),
      $request_stack
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    $request = \Drupal::requestStack()->getMasterRequest();
    $search_page = $request->attributes->get('entity');
    if ($search_page instanceof SearchPageInterface) {
      return '/search/' . $search_page->getPath();
    }
    return '/';
  }

  /**
   * {@inheritdoc}
   */
  public function fillFacetsWithResults($facets) {
    foreach ($facets as $facet) {
      $configuration = array(
        'query' => NULL,
        'facet' => $facet,
      );

      // Get the Facet Specific Query Type so we can process the results
      // using the build() function of the query type.
      $query_type = $this->queryTypePluginManager->createInstance($facet->getQueryType(), $configuration);
      $query_type->build();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    // Verify if the field exists. Otherwise the type will be a column
    // (type,uid...) from the node and we can use the field identifier directly.
    if ($field = FieldStorageConfig::loadByName('node', $facet->getFieldIdentifier())) {
      $field_type = $field->getType();
    }
    else {
      $field_type = $facet->getFieldIdentifier();
    }

    return $this->getQueryTypesForFieldType($field_type);
  }

  /**
   * Get the query types for a field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return array
   *   An array of query types.
   */
  protected function getQueryTypesForFieldType($field_type) {
    $query_types = [];
    switch ($field_type) {
      case 'type':
      case 'uid':
      case 'langcode':
      case 'integer':
      case 'entity_reference':
        $query_types['string'] = 'core_node_search_string';
        break;

      case 'created':
        $query_types['string'] = 'core_node_search_date';
        break;

    }

    return $query_types;
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    $request = \Drupal::requestStack()->getMasterRequest();
    $search_page = $request->attributes->get('entity');
    if ($search_page instanceof SearchPageInterface) {
      $facet_source_id = 'core_node_search:' . $search_page->id();
      if ($facet_source_id == $this->getPluginId()) {
        return TRUE;
      }
    }

    return FALSE;
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
    // Default fields.
    $facet_fields = $this->getDefaultFields();

    // Get the allowed field types.
    $allowed_field_types = \Drupal::moduleHandler()->invokeAll('facets_core_allowed_field_types', array($field_types = []));

    // Get the current field instances and detect if the field type is allowed.
    $fields = FieldConfig::loadMultiple();
    /** @var \Drupal\Field\FieldConfigInterface $field */
    foreach ($fields as $field) {
      // Verify if the target type is allowed for entity reference fields,
      // otherwise verify the field type(i.e. integer, float...).
      $target_is_allowed = in_array($field->getFieldStorageDefinition()->getSetting('target_type'), $allowed_field_types);
      $field_is_allowed = in_array($field->getFieldStorageDefinition()->getType(), $allowed_field_types);
      if ($target_is_allowed || $field_is_allowed) {
        /** @var \Drupal\field\Entity\FieldConfig $field */
        if (!array_key_exists($field->getName(), $facet_fields)) {
          $facet_fields[$field->getName()] = $this->t('@label', ['@label' => $field->getLabel()]);
        }
      }
    }

    return $facet_fields;
  }

  /**
   * Getter for default node fields.
   *
   * @return array
   *   An array containing the default fields enabled on a node.
   */
  protected function getDefaultFields() {
    return [
      'type' => $this->t('Content Type'),
      'uid' => $this->t('Author'),
      'langcode' => $this->t('Language'),
      'created' => $this->t('Post date'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFacetQueryExtender() {
    if (!$this->facetQueryExtender) {
      $this->facetQueryExtender = db_select('search_index', 'i', array('target' => 'replica'))->extend('Drupal\core_search_facets\FacetsQuery');
      $this->facetQueryExtender->join('node_field_data', 'n', 'n.nid = i.sid AND n.langcode = i.langcode');
      $this->facetQueryExtender
         // ->condition('n.status', 1).
         ->addTag('node_access')
         ->searchExpression($this->keys, 'node_search');
    }
    return $this->facetQueryExtender;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryInfo(FacetInterface $facet) {
    $query_info = [];
    $field_name = $facet->getFieldIdentifier();
    $default_fields = $this->getDefaultFields();
    if (array_key_exists($facet->getFieldIdentifier(), $default_fields)) {
      // We add the language code of the indexed item to the result of the
      // query. So in this case we need to use the search_index table alias (i)
      // for the langcode field. Otherwise we will have same nid for multiple
      // languages as result. For more details see NodeSearch::findResults().
      // @TODO review if I can refactor this.
      $table_alias = $facet->getFieldIdentifier() == 'langcode' ? 'i' : 'n';
      $query_info = [
        'fields' => [
          $table_alias . '.' . $facet->getFieldIdentifier() => [
            'table_alias' => $table_alias,
            'field' => $facet->getFieldIdentifier(),
          ],
        ],
      ];
    }
    else {
      // Gets field info, finds table name and field name.
      $table = "node__{$field_name}";
      // The column name will be different depending on the field type, it's
      // always the fields machine name, suffixed with '_value'. Entity
      // reference fields change that suffix into '_target_id'.
      $field_config = FieldStorageConfig::loadByName('node', $facet->getFieldIdentifier());
      $field_type = $field_config->getType();
      if ($field_type == 'entity_reference') {
        $column = $facet->getFieldIdentifier() . '_target_id';
      }
      else {
        $column = $facet->getFieldIdentifier() . '_value';
      }

      $query_info['fields'][$field_name . '.' . $column] = array(
        'table_alias' => $table,
        'field' => $column,
      );

      // Adds the join on the node table.
      $query_info['joins'] = array(
        $table => array(
          'table' => $table,
          'alias' => $table,
          'condition' => "n.vid = $table.revision_id AND i.langcode = $table.langcode",
        ),
      );
    }

    // Returns query info, makes sure all keys are present.
    return $query_info + [
      'joins' => [],
      'fields' => [],
    ];
  }

  /**
   * Checks if the search has facets.
   *
   * @TODO move to the Base class???
   */
  public function hasFacets() {
    $manager = \Drupal::service('entity_type.manager')->getStorage('facets_facet');
    $facets = $manager->loadMultiple();
    foreach ($facets as $facet) {
      if ($facet->getFacetSourceId() == $this->getPluginId()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
