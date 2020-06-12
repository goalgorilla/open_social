<?php

namespace Drupal\social_views_infinite_scroll;

/**
 * Interface SocialInfiniteScrollManagerInterface.
 */
interface SocialInfiniteScrollManagerInterface {

  /**
   * The module name.
   */
  const MODULE_NAME = 'social_views_infinite_scroll';

  /**
   * Get all available views from social infinite scroll settings.
   *
   * @return array
   */
  public function getAllViews();

  /**
   * Get only enabled views from social infinite scroll settings.
   *
   * @return array
   */
  public function getEnabledViews();
}
