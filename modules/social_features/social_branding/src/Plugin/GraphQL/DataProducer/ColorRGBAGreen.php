<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\RGBAColor;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The green component from RGBA color.
 *
 * @DataProducer(
 *   id = "color_rgba_green",
 *   name = @Translation("RGBA green component"),
 *   description = @Translation("The RGBA green component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("RGBA green component")
 *   ),
 *   consumes = {
 *     "rgba" = @ContextDefinition("any",
 *       label = @Translation("RGBA Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRGBAGreen extends EntityDataProducerPluginBase {

  /**
   * Returns the RGBA green component.
   *
   * @param \Drupal\social_branding\Wrappers\RGBAColor $rgba
   *   The RGBA color.
   *
   * @return int
   *   The RGBA green component.
   */
  public function resolve(RGBAColor $rgba) : int {
    return $rgba->green();
  }

}
