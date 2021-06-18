<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\RGBAColor;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The red component from RGBA color.
 *
 * @DataProducer(
 *   id = "color_rgba_red",
 *   name = @Translation("RGBA red component"),
 *   description = @Translation("The RGBA red component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("RGBA red component")
 *   ),
 *   consumes = {
 *     "rgba" = @ContextDefinition("any",
 *       label = @Translation("RGBA Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRGBARed extends EntityDataProducerPluginBase {

  /**
   * Returns the RGBA red component.
   *
   * @param \Drupal\social_branding\Wrappers\RGBAColor $rgba
   *   The RGBA color.
   *
   * @return int
   *   The RGBA red component.
   */
  public function resolve(RGBAColor $rgba) : int {
    return $rgba->red();
  }

}
