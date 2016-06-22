<?php

namespace Drupal\search_api\Datasource;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Plugin\IndexPluginBase;

/**
 * Defines a base class from which other datasources may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_datasource_info_alter(). The definition includes the
 * following keys:
 * - id: The unique, system-wide identifier of the datasource.
 * - label: The human-readable name of the datasource, translated.
 * - description: A human-readable description for the datasource, translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiDatasource(
 *   id = "my_datasource",
 *   label = @Translation("My datasource"),
 *   description = @Translation("Exposes my custom items as a datasource."),
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiDatasource
 * @see \Drupal\search_api\Datasource\DatasourcePluginManager
 * @see \Drupal\search_api\Datasource\DatasourceInterface
 * @see plugin_api
 */
abstract class DatasourcePluginBase extends IndexPluginBase implements DatasourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $items = $this->loadMultiple(array($id));
    return $items ? reset($items) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemLabel(ComplexDataInterface $item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemBundle(ComplexDataInterface $item) {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemUrl(ComplexDataInterface $item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes($bundle = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return array(
      $this->getPluginId() => $this->label(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewItem(ComplexDataInterface $item, $view_mode, $langcode = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultipleItems(array $items, $view_mode, $langcode = NULL) {
    $build = array();
    foreach ($items as $key => $item) {
      $build[$key] = $this->viewItem($item, $view_mode, $langcode);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemIds($page = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDependencies(array $fields) {
    return array();
  }

}
