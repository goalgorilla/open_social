<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The alpha component from color.
 *
 * @DataProducer(
 *   id = "color_alpha",
 *   name = @Translation("Color alpha component"),
 *   description = @Translation("The color alpha component."),
 *   produces = @ContextDefinition("float",
 *     label = @Translation("Color alpha component")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorAlpha extends EntityDataProducerPluginBase {

  /**
   * Returns the color alpha component.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The color.
   *
   * @return float
   *   The color alpha component.
   */
  public function resolve(Color $color) : float {
    return $color->alpha();
  }

}
