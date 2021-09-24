<?php

namespace Drupal\social_scroll;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class SocialScrollManager.
 */
class SocialScrollManager implements SocialScrollManagerInterface {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SocialScrollManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllAvailableViewIds(): array {
    return $this->configFactory->getEditable('social_scroll.settings')->getOriginal('views_list') ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledViewIds(): array {
    return array_filter($this->getAllAvailableViewIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockedViewIds(): array {
    return [
      // Some system or distro views.
      'who_voted_this_entity',
      'who_liked_this_entity',
      'watchdog',
      'user_admin_people',
      'report_overview',
      'redirect',
      'recipient_group_reference',
      'inbox',
      'featured_profile_reference',
      'featured_group_reference',
      'featured_content_reference',
      'event_manage_enrollments',
      'event_enrollments',
      'content',
      'community_activities',
      'comment',
      'activity_stream_profile',
      'activity_stream_notifications',
      'activity_stream_group',
      'activity_stream',
      // Temporarily here because ajax does not work correctly with these views,
      // probably because of we have a lot config overrides for these views.
      'search_all',
      'search_all_autocomplete',
      'search_content',
      'search_groups',
      'search_users',
      // Replace the results to no result.
      'events',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName(string $view_id): string {
    return 'views.view.' . $view_id;
  }

}
