<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The Hex color.
 *
 * @DataProducer(
 *   id = "color_hex",
 *   name = @Translation("Hex Color"),
 *   description = @Translation("The Hex color."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Hex Color")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorHex extends EntityDataProducerPluginBase {

  /**
   * Returns the Hex color.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The branding color.
   *
   * @return string
   *   The branding color CSS codification.
   */
  public function resolve(Color $color) : string {
    return $color->hexRgb();
  }

}
