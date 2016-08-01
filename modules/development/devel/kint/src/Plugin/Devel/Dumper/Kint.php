<?php

/**
 * @file
 * Contains \Drupal\kint\Plugin\Devel\Dumper\Kint.
 */

namespace Drupal\kint\Plugin\Devel\Dumper;

use Drupal\devel\DevelDumperBase;

/**
 * Provides a Kint dumper plugin.
 *
 * @DevelDumper(
 *   id = "kint",
 *   label = @Translation("Kint"),
 *   description = @Translation("Wrapper for Kint debugging tool."),
 * )
 */
class Kint extends DevelDumperBase {

  /**
   * Constructs a KintDevelDumper object.
   *
   * @TODO find another solution for kint class inclusion!
   */
  public function __construct() {
    kint_require();
  }

  /**
   * {@inheritdoc}
   */
  public function dump($input, $name = NULL) {
    if ($name) {
      $input = [(string) $name => $input];
    }

    \Kint::dump($input);
  }

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    if ($name) {
      $input = [(string) $name => $input];
    }

    $dump = @\Kint::dump($input);
    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements() {
    return kint_require();
  }

}
