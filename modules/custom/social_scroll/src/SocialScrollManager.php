<?php

namespace Drupal\social_scroll;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SocialScrollManager.
 */
class SocialScrollManager implements SocialScrollManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SocialScrollManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
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
    return array_keys(array_filter($this->getAllAvailableViewIds()));
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedViewIds(): array {
    $allowed_ids = [
      // Overview pages.
      'newest_groups',
      'latest_topics',
      'upcoming_events',
      'newest_users',
      // User overview pages.
      'groups',
      'topics',
      'events',
      // Group overview pages.
      'group_topics',
      'group_events',
      'group_members',
    ];

    $this->moduleHandler->alter('social_scroll_allowed_views', $allowed_ids);

    return $allowed_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName(string $view_id): string {
    return 'views.view.' . $view_id;
  }

}
