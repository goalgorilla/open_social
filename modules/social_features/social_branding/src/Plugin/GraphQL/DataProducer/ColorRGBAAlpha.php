<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\RGBAColor;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The alpha component from RGBA color.
 *
 * @DataProducer(
 *   id = "color_rgba_alpha",
 *   name = @Translation("RGBA alpha component"),
 *   description = @Translation("The RGBA alpha component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("RGBA alpha component")
 *   ),
 *   consumes = {
 *     "rgba" = @ContextDefinition("any",
 *       label = @Translation("RGBA Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRGBAAlpha extends EntityDataProducerPluginBase {

  /**
   * Returns the RGBA alpha component.
   *
   * @param \Drupal\social_branding\Wrappers\RGBAColor $rgba
   *   The RGBA color.
   *
   * @return int
   *   The RGBA alpha component.
   */
  public function resolve(RGBAColor $rgba) : int {
    return $rgba->alpha();
  }

}
