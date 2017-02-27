<?php

/**
 * @file
 * Contains \Drupal\socialbase\Plugin\Preprocess\InputButton.
 */

namespace Drupal\socialbase\Plugin\Preprocess;

/**
 * Pre-processes variables for the "input__button" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("input__button")
 */
class InputButton extends \Drupal\bootstrap\Plugin\Preprocess\InputButton {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);
    $variables['attributes']->removeClass('btn-default');
  }

}
