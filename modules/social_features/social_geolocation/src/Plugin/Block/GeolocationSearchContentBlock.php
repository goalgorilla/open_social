<?php

namespace Drupal\social_geolocation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GeolocationSearchContentBlock' block.
 *
 * @Block(
 *  id = "geolocation_search_content_block",
 *  admin_label = @Translation("Geolocation search content block"),
 * )
 */
class GeolocationSearchContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $form = \Drupal::formBuilder()->getForm('Drupal\social_geolocation\Form\GeolocationSearchContentForm');
    $build['search_form'] = $form;

    return $build;
  }

}
