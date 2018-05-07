Build upon Drupal 8 core multi-lingual modules:
- interface
- config_translation
- content_translation

This module provides a change in the behavior of the Core modules by showing content in all languages.
In order to facilitate this the SocialLanguageMetadataBubblingUrlGenerator class was made to transfer
all links to the current language that is detected.

In order to support this change with language URL detection a custom language switcher block was created to use the 
MetadataBubblingUrlGenerator from core to generate a URL.

In order to enable translations for other entities either go to the content translation UI form `admin/config/regional/content-language`
or add it in config by setting translatable settings on true on the fields and enable the content translation with a language config file: e.g. `language.content_settings.node.book.yml`
