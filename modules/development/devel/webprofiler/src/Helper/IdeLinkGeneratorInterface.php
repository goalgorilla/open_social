<?php

/**
 * @file
 * Contains Drupal\webprofiler\IdeLinkGeneratorInterface.
 */

namespace Drupal\webprofiler\Helper;

/**
 * Interface IdeLinkGeneratorInterface.
 */
interface IdeLinkGeneratorInterface {

  /**
   * @param $file
   * @param $line
   *
   * @return string
   */
  public function generateLink($file, $line);
}
