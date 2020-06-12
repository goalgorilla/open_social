<?php

namespace Drupal\social_views_infinite_scroll;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class SocialInfiniteScrollManager.
 */
class SocialInfiniteScrollManager {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SocialInfiniteScrollManager object.
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
  public function getAllViews() {
    return $this->configFactory->getEditable('social_views_infinite_scroll.settings')->getOriginal();
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledViews() {
    $all_views = $this->getAllViews();
    $enabled_views = [];

    foreach ($all_views as $key => $view) {
      foreach ($view as $status) {
        if ($status) {
          $enabled_views[$key] = $view;
          continue;
        }
      }
    }

    return $enabled_views;
  }

}
