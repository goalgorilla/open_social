<?php

namespace Drupal\social_branding\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\social_branding\Wrappers\Color;
use Drupal\social_graphql\Plugin\GraphQL\DataProducer\Entity\EntityDataProducerPluginBase;

/**
 * Get the proper branding color by given color name.
 *
 * The color name is the related configuration key we use in the configuration
 * files, so we use different consumers to get it dynamically:
 * - configName: socialblue.settings (socialblue theme settings by default)
 * - paletteName: color.theme.socialblue (socialblue theme settings customized)
 *
 * @DataProducer(
 *   id = "platform_branding_colors_load_color_by_name",
 *   name = @Translation("Platform Branding Colors Color By Name"),
 *   description = @Translation("The brand color."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Color")
 *   ),
 *   consumes = {
 *     "brandingColors" = @ContextDefinition("any",
 *       label = @Translation("Platform Branding Colors"),
 *       required = TRUE
 *     ),
 *     "paletteName" = @ContextDefinition("string",
 *       label = @Translation("Platform Brand Palette Color Name"),
 *       required = TRUE
 *     ),
 *     "configName" = @ContextDefinition("string",
 *       label = @Translation("Platform Brand Config Color Name"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class PlatformBrandingColorsColorByName extends EntityDataProducerPluginBase {

  /**
   * Returns the accent background brand color.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $branding_colors
   *   The branding colors.
   * @param string $palette_color_name
   *   The palette color name.
   * @param string $config_color_name
   *   The config color name.
   *
   * @return \Drupal\social_branding\Wrappers\Color
   *   The accent background brand color.
   */
  public function resolve(ImmutableConfig $branding_colors, string $palette_color_name, string $config_color_name) : Color {
    if ($customBrandColor = $branding_colors->get('palette.' . $palette_color_name)) {
      return new Color($customBrandColor);
    }
    return new Color($branding_colors->get('color_' . $config_color_name));
  }

}
