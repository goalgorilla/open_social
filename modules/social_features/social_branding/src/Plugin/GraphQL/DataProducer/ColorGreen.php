<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The green component from color.
 *
 * @DataProducer(
 *   id = "color_green",
 *   name = @Translation("Color green component"),
 *   description = @Translation("The color green component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("Color green component")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorGreen extends EntityDataProducerPluginBase {

  /**
   * Returns the color green component.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The color.
   *
   * @return int
   *   The color green component.
   */
  public function resolve(Color $color) : int {
    return $color->green();
  }

}
