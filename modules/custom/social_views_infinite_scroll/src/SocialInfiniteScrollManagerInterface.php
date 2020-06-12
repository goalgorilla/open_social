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
   *   All available views from social infinite scroll settings.
   */
  public function getAllViews();

  /**
   * Get only enabled views from social infinite scroll settings.
   *
   * @return array
   *   Enabled views from social infinite scroll settings.
   */
  public function getEnabledViews();

}
