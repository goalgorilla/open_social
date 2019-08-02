<?php

namespace Drupal\social_lazy_loading_images;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialLazyLoadingTextFormatOverride.
 *
 * @package Drupal\social_lazy_loading_images
 */
class SocialLazyLoadingImageDisplayOverride implements ConfigFactoryOverrideInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_fields = [
      // Event
      'core.entity_view_display.node.event.teaser' => 'field_event_image',
      'core.entity_view_display.node.event.hero' => 'field_event_image',
      'core.entity_view_display.node.event.activity' => 'field_event_image',
      'core.entity_view_display.node.event.activity_comment' => 'field_event_image',
      // Topic
      'core.entity_view_display.node.topic.teaser' => 'field_topic_image',
      'core.entity_view_display.node.topic.activity' => 'field_topic_image',
      'core.entity_view_display.node.topic.activity_comment' => 'field_topic_image',
      // Page
      'core.entity_view_display.node.page.teaser' => 'field_page_image',
      'core.entity_view_display.node.page.activity' => 'field_page_image',
      'core.entity_view_display.node.page.activity_comment' => 'field_page_image',
      // Groups
      'core.entity_view_display.group.open_group.teaser' => 'field_group_image',
      'core.entity_view_display.group.open_group.hero' => 'field_group_image',
      'core.entity_view_display.group.secret_group.teaser' => 'field_group_image',
      'core.entity_view_display.group.secret_group.hero' => 'field_group_image',
      'core.entity_view_display.group.flexible_group.teaser' => 'field_group_image',
      'core.entity_view_display.group.flexible_group.hero' => 'field_group_image',
      'core.entity_view_display.group.public_group.teaser' => 'field_group_image',
      'core.entity_view_display.group.public_group.hero' => 'field_group_image',
      'core.entity_view_display.group.closed_group.teaser' => 'field_group_image',
      'core.entity_view_display.group.closed_group.hero' => 'field_group_image',
      // Posts
      'core.entity_view_display.post.photo.activity' => 'field_post_image',
      'core.entity_view_display.post.photo.activity_comment' => 'field_post_image',
      'core.entity_view_display.post.photo.default' => 'field_post_image',
      // Comments dont have image fields.
      // Books
      'core.entity_view_display.node.book.teaser' => 'field_book_image',
      'core.entity_view_display.node.book.activity' => 'field_book_image',
      'core.entity_view_display.node.book.activity_comment' => 'field_book_image',
      // Profile
      'core.entity_view_display.profile.profile.compact' => 'field_profile_image',
      'core.entity_view_display.profile.profile.compact_notification' => 'field_profile_image',
      'core.entity_view_display.profile.profile.compact_teaser' => 'field_profile_image',
      'core.entity_view_display.profile.profile.hero' => 'field_profile_image',
      'core.entity_view_display.profile.profile.small' => 'field_profile_image',
      'core.entity_view_display.profile.profile.small_teaser' => 'field_profile_image',
      'core.entity_view_display.profile.profile.table' => 'field_profile_image',
      'core.entity_view_display.profile.profile.teaser' => 'field_profile_image',
    ];

    foreach ($config_fields as $config_name => $field_name) {
      $this->addImageOverride($config_name, $field_name, $overrides);
    }

    return $overrides;
  }

  /**
   * Alters the filter settings for the text format.
   *
   * @param string $config_name
   *   A config name to override.
   * @param string $field_name
   *   A field to override.
   * @param array $overrides
   *   An override configuration.
   */
  protected function addImageOverride($config_name, $field_name, array &$overrides) {
    if (!empty($config_name) && !empty($field_name)) {
      $config = $this->configFactory->getEditable($config_name);
      $dependencies = $config->getOriginal('dependencies.module');
      $overrides[$config_name]['dependencies']['module'] = $dependencies;
      $overrides[$config_name]['dependencies']['module'][] = 'lazy';

      $settings = $config->getOriginal('content.' . $field_name . 'third_party_settings');

      $overrides[$config_name]['content'][$field_name]['third_party_settings'] = $settings;
      $overrides[$config_name]['content'][$field_name]['third_party_settings'] = [
        'lazy' => [
          'lazy_image' => '1',
        ],
      ];
    }
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
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialLazyLoadingTextFormatOverride';
  }

}
