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
 *   id = "branding_color_scheme_load_color_by_name",
 *   name = @Translation("Color Scheme Color By Name"),
 *   description = @Translation("The brand color."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Color")
 *   ),
 *   consumes = {
 *     "colorScheme" = @ContextDefinition("any",
 *       label = @Translation("Color Scheme"),
 *       required = TRUE
 *     ),
 *     "paletteName" = @ContextDefinition("string",
 *       label = @Translation("Community Brand Palette Color Name"),
 *       required = TRUE
 *     ),
 *     "configName" = @ContextDefinition("string",
 *       label = @Translation("Community Brand Config Color Name"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class BrandingColorSchemeLoadColorByName extends EntityDataProducerPluginBase {

  /**
   * Returns the brand color by name.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $color_scheme
   *   The color scheme.
   * @param string $palette_color_name
   *   The palette color name.
   * @param string $config_color_name
   *   The config color name.
   *
   * @return \Drupal\social_branding\Wrappers\Color
   *   The brand color.
   */
  public function resolve(ImmutableConfig $color_scheme, string $palette_color_name, string $config_color_name) : Color {
    if ($customColor = $color_scheme->get('palette.' . $palette_color_name)) {
      return new Color($customColor);
    }
    return new Color($color_scheme->get('color_' . $config_color_name));
  }

}
