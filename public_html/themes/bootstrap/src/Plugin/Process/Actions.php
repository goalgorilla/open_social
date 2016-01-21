<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Process\Actions.
 */

namespace Drupal\bootstrap\Plugin\Process;

use Drupal\bootstrap\Annotation\BootstrapProcess;
use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Processes the "actions" element.
 *
 * @BootstrapProcess("actions")
 */
class Actions extends ProcessBase implements ProcessInterface {

  /**
   * {@inheritdoc}
   */
  public static function processElement(Element $element, FormStateInterface $form_state, array &$complete_form) {
    foreach ($element->children() as $child) {
      $child->setIcon();
    }
  }

}
