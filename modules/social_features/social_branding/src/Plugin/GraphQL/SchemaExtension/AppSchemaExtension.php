<?php

namespace Drupal\social_branding\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Adds app data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_branding_schema_extension",
 *   name = "Open Social - App Schema Extension",
 *   description = "GraphQL schema extension for Open Social app data.",
 *   schema = "open_social"
 * )
 */
class AppSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    // Query fields.
    $registry->addFieldResolver('Query', 'platformBranding',
      $builder->produce('platform_branding')
    );

    // PlatformBranding fields.
    $registry->addFieldResolver('PlatformBranding', 'logoUrl',
      $builder->produce('platform_branding_logo_url')
        ->map('platformBranding', $builder->fromParent())
    );
    $registry->addFieldResolver('PlatformBranding', 'brandingColors',
      $builder->produce('platform_branding_colors')
        ->map('platformBranding', $builder->fromParent())
    );

    // PlatformBrandColorScheme fields.
    $registry->addFieldResolver('PlatformBrandColorScheme', 'primary',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-primary'))
        ->map('configName', $builder->fromValue('primary'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'secondary',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-secondary'))
        ->map('configName', $builder->fromValue('secondary'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'accentBackground',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-accent'))
        ->map('configName', $builder->fromValue('accent'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'accentText',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-accent-text'))
        ->map('configName', $builder->fromValue('accent_text'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'link',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-link'))
        ->map('configName', $builder->fromValue('link'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarBackground',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-bg'))
        ->map('configName', $builder->fromValue('navbar_bg'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarText',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-text'))
        ->map('configName', $builder->fromValue('navbar_text'))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarActiveBackground',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-active-bg'))
        ->map('configName', $builder->fromValue("navbar_active_bg'"))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarActiveText',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-active-text'))
        ->map('configName', $builder->fromValue("navbar_active_text'"))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarSecondaryBackground',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-sec-bg'))
        ->map('configName', $builder->fromValue("navbar_sec_bg'"))
    );
    $registry->addFieldResolver('PlatformBrandColorScheme', 'navbarSecondaryText',
      $builder->produce('platform_branding_colors_load_color_by_name')
        ->map('brandingColors', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-sec-text'))
        ->map('configName', $builder->fromValue("navbar_sec_text'"))
    );

    // Color fields.
    $registry->addFieldResolver('Color', 'hexRGB',
      $builder->produce('color_hex')
        ->map('color', $builder->fromParent())
    );
    $registry->addFieldResolver('Color', 'rgba',
      $builder->produce('color_rgba')
        ->map('color', $builder->fromParent())
    );
    $registry->addFieldResolver('Color', 'css',
      $builder->produce('color_css')
        ->map('color', $builder->fromParent())
    );

    // RGBA Color fields.
    $registry->addFieldResolver('RGBAColor', 'red',
      $builder->produce('color_red')
        ->map('color', $builder->fromParent())
    );
    $registry->addFieldResolver('RGBAColor', 'green',
      $builder->produce('color_green')
        ->map('color', $builder->fromParent())
    );
    $registry->addFieldResolver('RGBAColor', 'blue',
      $builder->produce('color_blue')
        ->map('color', $builder->fromParent())
    );
    $registry->addFieldResolver('RGBAColor', 'alpha',
      $builder->produce('color_alpha')
        ->map('color', $builder->fromParent())
    );
  }

}
