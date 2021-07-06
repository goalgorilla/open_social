<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The CSS color.
 *
 * @DataProducer(
 *   id = "color_css",
 *   name = @Translation("CSS Color"),
 *   description = @Translation("A color value that is valid for the CSS 'color' property. Any CSS spec compliant string."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("CSS Color")
 *   ),
 *   consumes = {
 *     "color" = @ContextDefinition("any",
 *       label = @Translation("Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorCSS extends EntityDataProducerPluginBase {

  /**
   * Returns the CSS color.
   *
   * @param \Drupal\social_branding\Wrappers\Color $color
   *   The branding color.
   *
   * @return string
   *   The branding color CSS codification.
   */
  public function resolve(Color $color) : string {
    return $color->css();
  }

}
