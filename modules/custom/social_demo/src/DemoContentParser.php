<?php

namespace Drupal\social_demo;

use Symfony\Component\Yaml\Yaml;

class DemoContentParser extends Yaml implements DemoContentParserInterface {

  /**
   * {@inheritdoc}
   */
  public function getPath($file, $module) {
    return drupal_get_path('module', $module) . DIRECTORY_SEPARATOR . $file;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile($file, $module) {
    return $this->parse($this->getPath($file, $module));
  }

}
