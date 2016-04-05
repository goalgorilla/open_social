<?php

namespace Drupal\facets\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for facet source configuration.
 */
class FacetSourceController extends ControllerBase {

  /**
   * Configuration for the facet source.
   *
   * @param string $source_id
   *   The plugin id.
   *
   * @return array
   *   A renderable array containing the form.
   */
  public function facetSourceConfigForm($source_id) {
    // Returns the render array of the FacetSourceConfigForm.
    return $this->formBuilder()->getForm('\Drupal\facets\Form\FacetSourceEditForm');
  }

}
