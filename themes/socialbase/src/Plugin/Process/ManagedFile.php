<?php

namespace Drupal\socialbase\Plugin\Process;

use Drupal\bootstrap\Utility\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bootstrap\Plugin\Process\ManagedFile as BaseManagedFile;

/**
 * Processes the "managed_file" element.
 *
 * @ingroup plugins_process
 *
 * @BootstrapProcess("managed_file")
 */
class ManagedFile extends BaseManagedFile {

  /**
   * {@inheritdoc}
   */
  public static function processElement(Element $element, FormStateInterface $form_state, array &$complete_form) {
    $ajax_wrapper_id = $element->upload_button->getProperty('ajax')['wrapper'];
    if ($prefix = $element->getProperty('prefix')) {
      $prefix = preg_replace('/<div id="' . $ajax_wrapper_id . '">/', '<div id="' . $ajax_wrapper_id . '">', $prefix);
      $element->setProperty('prefix', $prefix);
    }
  }

}
