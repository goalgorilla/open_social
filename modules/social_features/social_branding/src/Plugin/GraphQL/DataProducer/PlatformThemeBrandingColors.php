<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * The brand colors for this platform.
 *
 * @DataProducer(
 *   id = "platform_theme_branding_colors",
 *   name = @Translation("Platform Theme Branding Colors"),
 *   description = @Translation("The brand colors for this platform."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Platform Theme Branding Colors")
 *   ),
 *   consumes = {
 *     "platformTheme" = @ContextDefinition("any",
 *       label = @Translation("Platform Theme"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformThemeBrandingColors extends EntityDataProducerPluginBase {

  /**
   * Returns platform theme branding colors.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $platform_theme
   *   The participant entity.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The platform theme branding colors.
   */
  public function resolve(ImmutableConfig $platform_theme) : ?ImmutableConfig {
    return $platform_theme;
  }

}
