<?php

namespace Drupal\social_lazy_loading_images;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class SocialLazyLoadingTextFormatOverride.
 *
 * @package Drupal\social_lazy_loading_images
 */
class SocialLazyLoadingImageDisplayOverride implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_fields = [
      // Event.
      'core.entity_view_display.node.event.teaser' => 'field_event_image',
      'core.entity_view_display.node.event.hero' => 'field_event_image',
      'core.entity_view_display.node.event.activity' => 'field_event_image',
      'core.entity_view_display.node.event.activity_comment' => 'field_event_image',
      // Topic.
      'core.entity_view_display.node.topic.teaser' => 'field_topic_image',
      'core.entity_view_display.node.topic.activity' => 'field_topic_image',
      'core.entity_view_display.node.topic.activity_comment' => 'field_topic_image',
      // Page.
      'core.entity_view_display.node.page.teaser' => 'field_page_image',
      'core.entity_view_display.node.page.activity' => 'field_page_image',
      'core.entity_view_display.node.page.activity_comment' => 'field_page_image',
      // Groups.
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
      // Posts.
      'core.entity_view_display.post.photo.activity' => 'field_post_image',
      'core.entity_view_display.post.photo.activity_comment' => 'field_post_image',
      'core.entity_view_display.post.photo.default' => 'field_post_image',
      // Books.
      'core.entity_view_display.node.book.teaser' => 'field_book_image',
      'core.entity_view_display.node.book.activity' => 'field_book_image',
      'core.entity_view_display.node.book.activity_comment' => 'field_book_image',
      // Profile.
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
      if (in_array($config_name, $names)) {
        $overrides[$config_name]['dependencies']['module']['lazy'] = 'lazy';
        $overrides[$config_name]['content'][$field_name]['third_party_settings']['lazy'] = ['lazy_image' => '1'];
      }
    }

    return $overrides;
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
    return 'SocialLazyLoadingImageDisplayOverride';
  }

}
