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
    $registry->addFieldResolver('Query', 'about',
      $builder->produce('community_about')
    );
    $registry->addFieldResolver('Query', 'branding',
      $builder->produce('community_branding')
    );
    $registry->addFieldResolver('Query', 'preferredFeatures',
      $builder->produce('preferred_features')
    );

    // CommunityAbout fields.
    $registry->addFieldResolver('CommunityAbout', 'name',
      $builder->produce('about_name')
        ->map('communityAbout', $builder->fromParent())
    );

    // CommunityBranding fields.
    $registry->addFieldResolver('CommunityBranding', 'logoUrl',
      $builder->produce('branding_logo_url')
        ->map('communityBranding', $builder->fromParent())
    );
    $registry->addFieldResolver('CommunityBranding', 'colorScheme',
      $builder->produce('branding_color_scheme')
        ->map('communityBranding', $builder->fromParent())
    );

    // BrandingColorScheme fields.
    $registry->addFieldResolver('BrandingColorScheme', 'primary',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-primary'))
        ->map('configName', $builder->fromValue('primary'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'secondary',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-secondary'))
        ->map('configName', $builder->fromValue('secondary'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'accentBackground',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-accent'))
        ->map('configName', $builder->fromValue('accent'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'accentText',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-accent-text'))
        ->map('configName', $builder->fromValue('accent_text'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'link',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('brand-link'))
        ->map('configName', $builder->fromValue('link'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarBackground',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-bg'))
        ->map('configName', $builder->fromValue('navbar_bg'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarText',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-text'))
        ->map('configName', $builder->fromValue('navbar_text'))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarActiveBackground',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-active-bg'))
        ->map('configName', $builder->fromValue("navbar_active_bg'"))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarActiveText',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-active-text'))
        ->map('configName', $builder->fromValue("navbar_active_text'"))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarSecondaryBackground',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
        ->map('paletteName', $builder->fromValue('navbar-sec-bg'))
        ->map('configName', $builder->fromValue("navbar_sec_bg'"))
    );
    $registry->addFieldResolver('BrandingColorScheme', 'navbarSecondaryText',
      $builder->produce('branding_color_scheme_load_color_by_name')
        ->map('colorScheme', $builder->fromParent())
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

    // Feature fields.
    $registry->addFieldResolver('Feature', 'machineName',
      $builder->produce('feature_machine_name')
        ->map('preferredFeature', $builder->fromParent())
    );
  }

}
