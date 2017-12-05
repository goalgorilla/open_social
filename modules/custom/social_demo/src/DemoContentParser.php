<?php

namespace Drupal\social_demo;

use Symfony\Component\Yaml\Yaml;

/**
 * Class DemoContentParser.
 *
 * @package Drupal\social_demo
 */
class DemoContentParser extends Yaml implements DemoContentParserInterface {

  /**
   * {@inheritdoc}
   */
  public function getPath($file, $module, $profile) {
    if ($profile != '' && strpos($profile, DIRECTORY_SEPARATOR) === FALSE) {
      $profile .= DIRECTORY_SEPARATOR;
    }
    return drupal_get_path('module', $module) . DIRECTORY_SEPARATOR . $profile . $file;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile($file, $module, $profile) {
    return $this->parse(file_get_contents($this->getPath($file, $module, $profile)));
  }

}
