<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The brand colors for this platform.
 *
 * @DataProducer(
 *   id = "platform_branding_colors",
 *   name = @Translation("Platform Branding Colors"),
 *   description = @Translation("The brand colors for this platform."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Platform Branding Colors")
 *   ),
 *   consumes = {
 *     "platformBranding" = @ContextDefinition("any",
 *       label = @Translation("Platform Branding"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformBrandingColors extends EntityDataProducerPluginBase {

  /**
   * Returns platform branding colors.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $platform_branding
   *   The participant entity.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The platform branding colors.
   */
  public function resolve(ImmutableConfig $platform_branding) : ?ImmutableConfig {
    return $platform_branding;
  }

}
