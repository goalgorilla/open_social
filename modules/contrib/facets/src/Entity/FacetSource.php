<?php

namespace Drupal\facets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\facets\FacetSourceInterface;

/**
 * Defines the facet source configuration entity.
 *
 * @ConfigEntityType(
 *   id = "facets_facet_source",
 *   label = @Translation("Facet source"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\facets\FacetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\facets\Form\FacetSourceEditForm",
 *       "edit" = "Drupal\facets\Form\FacetSourceEditForm",
 *       "display" = "Drupal\facets\Form\FacetSourceDisplayForm",
 *       "delete" = "Drupal\facets\Form\FacetSourceDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer facets",
 *   config_prefix = "facet_source",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "filter_key",
 *     "url_processor"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/facets/facet-sources/",
 *     "edit-form" = "/admin/config/search/facets/facet-sources/{facets_facet_source}/edit"
 *   }
 * )
 */
class FacetSource extends ConfigEntityBase implements FacetSourceInterface {

  /**
   * The ID of the facet source.
   *
   * @var string
   */
  protected $id;

  /**
   * A name to be displayed for the facet source.
   *
   * @var string
   */
  protected $name;

  /**
   * The key, used for filters in the query string.
   *
   * @var string
   */
  protected $filter_key;

  /**
   * The url processor name.
   *
   * @var string
   */
  protected $url_processor = 'query_string';

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilterKey($filter_key) {
    $this->filter_key = $filter_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterKey() {
    return $this->filter_key;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrlProcessor($processor_name) {
    $this->url_processor = $processor_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlProcessorName() {
    return $this->url_processor;
  }

}
