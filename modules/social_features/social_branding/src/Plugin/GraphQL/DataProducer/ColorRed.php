<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The red component from color.
 *
 * @DataProducer(
 *   id = "color_red",
 *   name = @Translation("Color red component"),
 *   description = @Translation("The color red component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("Color red component")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRed extends EntityDataProducerPluginBase {

  /**
   * Returns the color red component.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The color.
   *
   * @return int
   *   The color red component.
   */
  public function resolve(Color $color) : int {
    return $color->red();
  }

}
