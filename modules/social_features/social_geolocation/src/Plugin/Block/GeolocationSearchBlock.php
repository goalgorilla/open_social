<?php

namespace Drupal\social_geolocation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GeolocationSearchBlock' block.
 *
 * @Block(
 *  id = "geolocation_search_block",
 *  admin_label = @Translation("Geolocation Search block"),
 * )
 */
class GeolocationSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_geolocation\Form\GeolocationSearchForm');
    $build['search_form'] = $form;

    return $build;
  }

}
