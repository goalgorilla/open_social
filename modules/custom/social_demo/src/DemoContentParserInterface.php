<?php

namespace Drupal\social_demo;

/**
 * Interface DemoContentParserInterface.
 *
 * @package Drupal\social_demo
 */
interface DemoContentParserInterface {

  /**
   * Returns the path for the given file.
   *
   * @param string $file
   *   The filename.
   * @param string $module
   *   The module where the Yaml file is placed.
   * @param string $profile
   *   The profile used.
   *
   * @return string
   *   String with the full pathname including the file.
   */
  public function getPath($file, $module, $profile);

  /**
   * Parses YAML file into a PHP value.
   *
   * @param string $file
   *   The filename.
   * @param string $module
   *   The module where the Yaml file is placed.
   * @param string $profile
   *   The profile used.
   *
   * @return mixed
   *   The YAML converted to a PHP value.
   */
  public function parseFileFromModule($file, $module, $profile);

}
