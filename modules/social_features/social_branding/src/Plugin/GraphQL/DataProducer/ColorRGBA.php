<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_branding\Wrappers\RGBAColor;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The RGBA color.
 *
 * @DataProducer(
 *   id = "color_rgba",
 *   name = @Translation("RGBA Color"),
 *   description = @Translation("The RGBA color."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("RGBA Color")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRGBA extends EntityDataProducerPluginBase {

  /**
   * Returns the RGBA color.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The branding color.
   *
   * @return \Drupal\social_branding\Wrappers\RGBAColor
   *   The branding color RGBA codification.
   */
  public function resolve(Color $color) : RGBAColor {
    return $color->getRgba();
  }

}
