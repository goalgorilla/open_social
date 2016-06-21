<?php

namespace Drupal\social_demo\Yaml;

/*
 * Social Demo Content Generator
 */
use Symfony\Component\Yaml\Yaml;

/**
 *
 */
class SocialDemoParser extends Yaml {

  /**
   *
   */
  private function getPath($file) {
    return '/root/dev-scripts/content/' . $file;
  }

  /**
   *
   */
  public function parseFile($file) {
    return $this->parse(file_get_contents($this->getPath($file)));
  }

}
