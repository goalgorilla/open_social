<?php

namespace Drupal\social_scroll;

/**
 * Interface SocialScrollManagerInterface.
 */
interface SocialScrollManagerInterface {

  /**
   * The module name.
   */
  const MODULE_NAME = 'social_scroll';

  /**
   * Get all available views from social infinite scroll settings.
   *
   * @return array
   *   All available view ids from social infinite scroll settings.
   */
  public function getAllAvailableViewIds();

  /**
   * Get only enabled views from social infinite scroll settings.
   *
   * @return array
   *   Enabled view ids from social infinite scroll settings.
   */
  public function getEnabledViewIds();

  /**
   * Get blocked views.
   *
   * @return array
   *   Some system and distro views.
   */
  public function getBlockedViewIds();

  /**
   * Get view config name by view ID.
   *
   * @param string $view_id
   *   The view ID.
   *
   * @return string
   *   The view config name.
   */
  public function getConfigName($view_id);

}
