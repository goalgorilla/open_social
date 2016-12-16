<?php

namespace Drupal\social_demo\Yaml;

/*
 * Social Demo Content Generator
 */
use Symfony\Component\Yaml\Yaml;

/**
 * Implements the SocialDemoParser to pass the demo content yml files.
 */
class SocialDemoParser extends Yaml {

  /**
   * Returns the path for the given file.
   *
   * @param string $file
   *   The filename string.
   * @param string $module
   *   The module where the Yaml file is placed.
   *
   * @return string
   *   String with the full pathname including the file.
   */
  public function getPath($file, $module = 'social_demo') {
    // @todo Fix this for other file paths?!.

    return drupal_get_path('module', $module) . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . $file;
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile($file, $module = 'social_demo') {
    return $this->parse(file_get_contents($this->getPath($file, $module)));
  }

}
