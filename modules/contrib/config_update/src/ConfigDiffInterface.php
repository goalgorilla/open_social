<?php

namespace Drupal\config_update;

/**
 * Defines an interface for config differences.
 */
interface ConfigDiffInterface {

  /**
   * Decides if two configuration arrays are considered to be the same.
   *
   * The two arrays are considered to be the same if, after "normalizing", they
   * have the same keys and values. It is up to the particular implementing
   * class to decide what normalizing means.
   *
   * @param array $source
   *   Source config.
   * @param array $target
   *   Target config.
   *
   * @return bool
   *   TRUE if the source and target are the same, and FALSE if they are
   *   different.
   */
  public function same($source, $target);

  /**
   * Calculates differences between config.
   *
   * The two arrays are "normalized" and split into lines to compare
   * differences. It is up to the particular implementing class to decide what
   * normalizing means.
   *
   * @param array $source
   *   Source config.
   * @param array $target
   *   Target config.
   *
   * @return \Drupal\Component\Diff\Diff
   *   Diff object for displaying line-by-line differences between source and
   *   target config.
   */
  public function diff($source, $target);

}
