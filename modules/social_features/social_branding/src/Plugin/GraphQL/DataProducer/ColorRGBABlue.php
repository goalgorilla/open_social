<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\social_branding\Wrappers\RGBAColor;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The blue component from RGBA color.
 *
 * @DataProducer(
 *   id = "color_rgba_blue",
 *   name = @Translation("RGBA blue component"),
 *   description = @Translation("The RGBA blue component."),
 *   produces = @ContextDefinition("int",
 *     label = @Translation("RGBA blue component")
 *   ),
 *   consumes = {
 *     "rgba" = @ContextDefinition("any",
 *       label = @Translation("RGBA Color"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ColorRGBABlue extends EntityDataProducerPluginBase {

  /**
   * Returns the RGBA blue component.
   *
   * @param \Drupal\social_branding\Wrappers\RGBAColor $rgba
   *   The RGBA color.
   *
   * @return int
   *   The RGBA blue component.
   */
  public function resolve(RGBAColor $rgba) : int {
    return $rgba->blue();
  }

}
