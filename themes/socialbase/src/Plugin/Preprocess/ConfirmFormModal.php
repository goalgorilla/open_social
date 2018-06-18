<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "confirm_form__modal" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("confirm_form__modal")
 */
class ConfirmFormModal extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    $form = &$variables['form'];

    $variables['title'] = $form['#title'];

    $form['actions']['cancel']['#attributes']['class'] = [
      'dialog-cancel',
      'btn',
      'btn-default',
      'pull-left',
    ];
  }

}
