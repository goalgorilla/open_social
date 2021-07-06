<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The blue component from color.
 *
 * @DataProducer(
 *   id = "color_blue",
 *   name = @Translation("Color blue component"),
 *   description = @Translation("The color blue component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("Color blue component")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorBlue extends EntityDataProducerPluginBase {

  /**
   * Returns the color blue component.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The color.
   *
   * @return int
   *   The color blue component.
   */
  public function resolve(Color $color) : int {
    return $color->blue();
  }

}
