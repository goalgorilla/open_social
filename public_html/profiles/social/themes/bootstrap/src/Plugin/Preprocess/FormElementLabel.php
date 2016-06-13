<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\FormElementLabel.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "form_element_label" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("form_element_label")
 */
class FormElementLabel extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    $variables->map(['attributes']);
    $this->preprocessAttributes($variables, $hook, $info);
  }

}
