<?php

namespace Drupal\facets\FacetSource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a base class from which other facet sources may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. The definition includes the following keys:
 * - id: The unique, system-wide identifier of the facet source.
 * - label: The human-readable name of the facet source, translated.
 * - description: A human-readable description for the facet source, translated.
 *
 * @see \Drupal\facets\Annotation\FacetsFacetSource
 * @see \Drupal\facets\FacetSource\FacetSourcePluginManager
 * @see \Drupal\facets\FacetSource\FacetSourcePluginInterface
 * @see plugin_api
 */
abstract class FacetSourcePluginBase extends PluginBase implements FacetSourcePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\facets\QueryType\QueryTypePluginManager
   */
  protected $queryTypePluginManager;

  /**
   * The search keys, or query text, submitted by the user.
   *
   * @var string
   */
  protected $keys;

  /**
   * The facet we're editing for.
   *
   * @var \Drupal\facets\FacetInterface
   */
  protected $facet;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queryTypePluginManager = $query_type_plugin_manager;

    if (isset($configuration['facet'])) {
      $this->facet = $configuration['facet'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Insert the plugin manager for query types.
    /** @var \Drupal\facets\QueryType\QueryTypePluginManager $query_type_plugin_manager */
    $query_type_plugin_manager = $container->get('plugin.manager.facets.query_type');

    return new static($configuration, $plugin_id, $plugin_definition, $query_type_plugin_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryTypesForFacet(FacetInterface $facet) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isRenderedInCurrentRequest() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSearchKeys($keys) {
    $this->keys = $keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $facet_source_id = $this->facet->getFacetSourceId();
    $field_identifier = $form_state->getValue('facet_source_configs')[$facet_source_id]['field_identifier'];
    $this->facet->setFieldIdentifier($field_identifier);
  }

}
