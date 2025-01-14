<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "join" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("join")
 */
class Join extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables): void {
    // We probably don't need .btn-group here, but I can't figure out why it's
    // needed, so it's only removed for the new user_is_invited set-up.
    $variables->addClass('btn-group');
  }

}
