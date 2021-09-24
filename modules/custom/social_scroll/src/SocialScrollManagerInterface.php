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
   * @return string[]
   *   All available view ids from social infinite scroll settings.
   */
  public function getAllAvailableViewIds(): array;

  /**
   * Get only enabled views from social infinite scroll settings.
   *
   * @return string[]
   *   Enabled view ids from social infinite scroll settings.
   */
  public function getEnabledViewIds(): array;

  /**
   * Get blocked views.
   *
   * @return string[]
   *   Some system and distro views.
   */
  public function getBlockedViewIds(): array;

  /**
   * Get view config name by view ID.
   *
   * @param string $view_id
   *   The view ID.
   *
   * @return string
   *   The view config name.
   */
  public function getConfigName(string $view_id): string;

}
