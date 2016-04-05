<?php

namespace Drupal\facets\Plugin\facets\facet_source;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\facets\FacetSource\FacetSourceDeriverBase;

/**
 * Derives a facet source plugin definition for every Search API view.
 *
 * This facet source only supports views that have a search api index as a base,
 * and only those displays that are a block or a page.
 *
 * @see \Drupal\facets\Plugin\facets\facet_source\SearchApiViews
 */
class SearchApiViewsDeriver extends FacetSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $base_plugin_id = $base_plugin_definition['id'];

    try {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $views_storage */
      $views_storage = $this->entityTypeManager->getStorage('view');
      $all_views = $views_storage->loadMultiple();
    }
    catch (PluginNotFoundException $e) {
      return [];
    }

    if (!isset($this->derivatives[$base_plugin_id])) {
      $plugin_derivatives = array();

      /** @var \Drupal\views\Entity\View $view */
      foreach ($all_views as $view) {
        // Hardcoded usage of Search API views, for now.
        if (strpos($view->get('base_table'), 'search_api_index') !== FALSE) {
          $displays = $view->get('display');
          foreach ($displays as $display_id => $display_info) {

            // We only support pages and blocks because those are the ones that
            // we've tested. They are also the only ones that support for
            // ::isRenderedInCurrentRequest() and ::getPath().
            if (in_array($display_info['display_plugin'], ['page', 'block'])) {
              $machine_name = $view->id() . PluginBase::DERIVATIVE_SEPARATOR . $display_id;

              $label_arguments = [
                '%view_name' => $view->label(),
                '%display_title' => $display_info['display_title'],
                '%display_type' => $display_info['display_plugin'],
              ];
              $plugin_derivatives[$machine_name] = [
                'id' => $base_plugin_id . PluginBase::DERIVATIVE_SEPARATOR . $machine_name,
                'label' => $this->t('Search API view: %view_name, display: %display_title (%display_type)',
                  $label_arguments
                ),
                'description' => $this->t('Provides a facet source.'),
                'view_id' => $view->id(),
                'view_display' => $display_id,
              ] + $base_plugin_definition;

              $sources[] = $this->t(
                'Search API view: %view, display: %display',
                ['%view' => $view->label(), '%display' => $display_info['display_title']]
              );
            }
          }
        }
      }
      uasort($plugin_derivatives, array($this, 'compareDerivatives'));

      $this->derivatives[$base_plugin_id] = $plugin_derivatives;
    }
    return $this->derivatives[$base_plugin_id];
  }

}
