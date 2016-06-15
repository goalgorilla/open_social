<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Utility\Crypt.
 */

namespace Drupal\bootstrap\Utility;

/**
 * Extends \Drupal\Component\Utility\Crypt.
 */
class Crypt extends \Drupal\Component\Utility\Crypt {

  /**
   * Generates a unique hash name.
   *
   * @param ...
   *   All arguments passed will be serialized and used to generate the hash.
   *
   * @return string
   *   The generated hash identifier.
   */
  public static function generateHash() {
    $args = func_get_args();
    $hash = '';
    if (is_string($args[0])) {
      $hash = $args[0] . ':';
    }
    elseif (is_array($args[0])) {
      $hash = implode(':', $args[0]) . ':';
    }
    $hash .= self::hashBase64(serialize($args));
    return $hash;
  }
}
