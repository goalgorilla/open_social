<?php

namespace Drupal\core_search_facets\Plugin\facets\facet_source;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives a facet source plugin definition for every Search API view.
 *
 * @see \Drupal\facets\Plugin\facets\facet_source\SearchApiViews
 */
class CoreNodeSearchFacetSourceDeriver extends FacetSourceDeriverBase {

  /**
   * The plugin manager for core search plugins.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * Creates an instance of the deriver.
   *
   * @param string $base_plugin_id
   *   The plugin ID.
   * @param \Drupal\search\SearchPluginManager $search_manager
   *   The plugin manager for core search plugins.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct($base_plugin_id, SearchPluginManager $search_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->searchManager = $search_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.search'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];

    if (!isset($this->derivatives[$base_plugin_id])) {
      $plugin_derivatives = [];

      $pages = $this->entityTypeManager->getStorage('search_page')->loadMultiple();

      foreach ($pages as $machine_name => $page) {
        /* @var \Drupal\search\Entity\SearchPage $page * */
        if ($page->get('plugin') == 'node_search') {
          // Detect if the plugin has "faceted" definition.
          $plugin_derivatives[$machine_name] = [
            'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
            'label' => $this->t('Core Search Page: %page_name', ['%page_name' => $page->get('label')]),
            'description' => $this->t('Provides a facet source.'),
          ] + $base_plugin_definition;
        }
        uasort($plugin_derivatives, array($this, 'compareDerivatives'));

        $this->derivatives[$base_plugin_id] = $plugin_derivatives;
      }
    }
    return $this->derivatives[$base_plugin_id];
  }

}
