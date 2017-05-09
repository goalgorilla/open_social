<?php

namespace Drupal\social_demo;

interface DemoContentParserInterface {

  /**
   * Returns the path for the given file.
   *
   * @param string $file
   *   The filename.
   * @param string $module
   *   The module where the Yaml file is placed.
   *
   * @return string
   *   String with the full pathname including the file.
   */
  public function getPath($file, $module);

  /**
   * Parses YAML file into a PHP value.
   *
   * @param string $file
   *   The filename.
   * @param string $module
   *   The module where the Yaml file is placed.
   *
   * @return mixed
   *   The YAML converted to a PHP value.
   */
  public function parseFile($file, $module);

}
