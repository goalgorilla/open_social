<?php

namespace Drupal\social_course;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides content translation defaults for the Social Course module.
 *
 * @package Drupal\social_course
 */
class ContentTranslationDefaultsConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ContentTranslationDefaultsConfigOverride object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // If the module "social_content_translation" is enabled let make
    // translations enabled for content provided by the module by default.
    $is_content_translations_enabled = $this->moduleHandler
      ->moduleExists('social_content_translation');

    if (!$is_content_translations_enabled) {
      return $overrides;
    }
    // Translations for "Article" node type.
    $config_name = 'language.content_settings.node.course_article';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_names = [
      'core.base_field_override.node.course_article.title',
      'core.base_field_override.node.course_article.menu_link ',
      'core.base_field_override.node.course_article.path',
      'core.base_field_override.node.course_article.uid',
      'core.base_field_override.node.course_article.status',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    // Translations for "Section" node type.
    $config_name = 'language.content_settings.node.course_section';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_names = [
      'core.base_field_override.node.course_section.title',
      'core.base_field_override.node.course_section.menu_link',
      'core.base_field_override.node.course_section.path',
      'core.base_field_override.node.course_section.uid',
      'core.base_field_override.node.course_section.status',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    // Translations for "Video" node type.
    $config_name = 'language.content_settings.node.course_video';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
          ],
        ],
      ];
    }
    $config_names = [
      'core.base_field_override.node.course_video.title',
      'core.base_field_override.node.course_video.menu_link ',
      'core.base_field_override.node.course_video.path',
      'core.base_field_override.node.course_video.uid',
      'core.base_field_override.node.course_video.status',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    // Translations for "Attachment" paragraph type.
    $config_name = 'language.content_settings.paragraph.attachment';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
            'bundle_settings' => [
              'untranslatable_fields_hide' => '0',
            ],
          ],
        ],
      ];
    }
    $config_names = [
      'core.base_field_override.paragraph.attachment.status',
    ];
    foreach ($config_names as $config_name) {
      if (in_array($config_name, $names)) {
        $overrides[$config_name] = [
          'translatable' => TRUE,
        ];
      }
    }

    // Translations for "Image + Text" paragraph type.
    $config_name = 'language.content_settings.paragraph.image_text';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
            'bundle_settings' => [
              'untranslatable_fields_hide' => '0',
            ],
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.image_text.status';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }
    $config_name = 'field.field.paragraph.image_text.field_image';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
      ];
    }

    // Translations for "Images" paragraph type.
    $config_name = 'language.content_settings.paragraph.images';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
            'bundle_settings' => [
              'untranslatable_fields_hide' => '0',
            ],
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.images.status';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }
    $config_name = 'field.field.paragraph.images.field_images';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
      ];
    }

    // Translations for "Text" paragraph type.
    $config_name = 'language.content_settings.paragraph.text';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
            'bundle_settings' => [
              'untranslatable_fields_hide' => '0',
            ],
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.text.status';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }

    // Translations for "Text + Image" paragraph type.
    $config_name = 'language.content_settings.paragraph.text_image';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'enabled' => TRUE,
            'bundle_settings' => [
              'untranslatable_fields_hide' => '0',
            ],
          ],
        ],
      ];
    }
    $config_name = 'core.base_field_override.paragraph.text_image.status';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'translatable' => TRUE,
      ];
    }
    $config_name = 'field.field.paragraph.text_image.field_image';
    if (in_array($config_name, $names)) {
      $overrides[$config_name] = [
        'third_party_settings' => [
          'content_translation' => [
            'translation_sync' => [
              'file' => 'file',
              'alt' => '0',
              'title' => '0',
            ],
          ],
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'social_course.content_translation_defaults_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION): ?StorableConfigBase {
    return NULL;
  }

}
