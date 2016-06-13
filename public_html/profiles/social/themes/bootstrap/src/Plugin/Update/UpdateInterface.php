<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Update\UpdateInterface.
 */

namespace Drupal\bootstrap\Plugin\Update;

use Drupal\bootstrap\Theme;

/**
 * Defines the interface for an object oriented preprocess plugin.
 */
interface UpdateInterface {

  /**
   * Retrieves the update description, if any.
   *
   * @return string
   *   The update description.
   */
  public function getDescription();

  /**
   * Retrieves the update level, if any.
   *
   * @return string
   *   The update level.
   */
  public function getLevel();

  /**
   * Retrieves the update human-readable title.
   *
   * @return string
   *   The update's title.
   */
  public function getTitle();

  /**
   * Update callback.
   *
   * @param \Drupal\bootstrap\Theme $theme
   *   The theme being updated.
   *
   * @return bool
   *   FALSE if the update failed, otherwise any other return will be
   *   interpreted as TRUE.
   */
  public function update(Theme $theme);

}
