# Social Language - Content Translation

This module has intentionally been left empty. It serves as a way to 
enable/disable content translation for the Open Social content types.

The configurations for the various content types should be kept in their own
modules with a dependency on this module. This way the translation settings 
can be kept up to date if fields change.

This module contains settings that are read by the configuration override 
classes for the content types to determine whether they should enable 
translation of that content type. By using optional configuration for the 
translatability of entities this is also only enabled together with this module.

## Modifying what content is translatable

If you need to change which fields for a content type are translatable then you
should probably use Drupal's content translation module configuration and simply
disble this module. This module exists to make an alternative UI possible for
sitemanagers because the content translation UI exposes too much in certain
scenarios.
