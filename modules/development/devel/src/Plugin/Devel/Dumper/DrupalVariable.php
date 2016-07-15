<?php

/**
 * @file
 * Contains \Drupal\devel\Plugin\Devel\Dumper\DrupalVariable.
 */

namespace Drupal\devel\Plugin\Devel\Dumper;

use Drupal\Component\Utility\Variable;
use Drupal\devel\DevelDumperBase;

/**
 * Provides a DrupalVariable dumper plugin.
 *
 * @DevelDumper(
 *   id = "drupal_variable",
 *   label = @Translation("Drupal variable."),
 *   description = @Translation("Wrapper for <a href='https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21Variable.php/class/Variable/8'>Drupal Variable</a> class.")
 * )
 */
class DrupalVariable extends DevelDumperBase {

  /**
   * {@inheritdoc}
   */
  public function dump($input, $name = NULL) {
    $name = $name ? $name . ' => ' : '';
    $output = Variable::export($input);
    echo '<pre>' . $name . print_r($output) . '</pre>';
  }

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    $name = $name ? $name . ' => ' : '';
    $dump = '<pre>' . $name . Variable::export($input) . '</pre>';
    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   */
  public function exportAsRenderable($input, $name = NULL) {
    $output['container'] = [
      '#type' => 'details',
      '#title' => $name ? : $this->t('Variable'),
      '#attached' => [
        'library' => ['devel/devel']
      ],
      '#attributes' => [
        'class' => ['container-inline', 'devel-dumper', 'devel-selectable'],
      ],
      'export' => [
        '#markup' => $this->export($input),
      ],
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements() {
    return TRUE;
  }

}
