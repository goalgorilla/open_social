<?php

namespace Drupal\facets\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\facets\FacetInterface;

/**
 * Provides route responses for facets.
 */
class FacetController extends ControllerBase {

  /**
   * Displays information about a search facet.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet to display.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(FacetInterface $facet) {
    // Build the search index information.
    $render = array(
      'view' => array(
        '#theme' => 'facets_facet',
        '#facet' => $facet,
      ),
    );
    return $render;
  }

  /**
   * Returns a form to add a new facet to a Search API index.
   *
   * @return array
   *   The facet add form.
   */
  public function addForm() {
    $facet = \Drupal::service('entity_type.manager')->getStorage('facets_facet')->create();
    return $this->entityFormBuilder()->getForm($facet, 'default');
  }

  /**
   * Returns a form to edit a facet on a Search API index.
   *
   * @param \Drupal\facets\FacetInterface $facets_facet
   *   Facet currently being edited.
   *
   * @return array
   *   The facet edit form.
   */
  public function editForm(FacetInterface $facets_facet) {
    $facet = \Drupal::service('entity_type.manager')->getStorage('facets_facet')->load($facets_facet->id());
    return $this->entityFormBuilder()->getForm($facet, 'default');
  }

  /**
   * Returns the page title for an facets's "View" tab.
   *
   * @param \Drupal\facets\FacetInterface $facet
   *   The facet that is displayed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(FacetInterface $facet) {
    return new FormattableMarkup('@title', array('@title' => $facet->label()));
  }

}
