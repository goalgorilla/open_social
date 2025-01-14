<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\socialbase\Plugin\Preprocess\Join as JoinBase;

/**
 * Pre-processes variables for the "join" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("join")
 */
class Join extends JoinBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables): void {
    parent::preprocessVariables($variables);

    if (
      $variables->offsetExists('user_is_invited') &&
      $variables->offsetGet('user_is_invited')
    ) {
      $variables
        ->replaceClass('btn-group', 'dropdown')
        ->addClass('form-group-inline');
    }
  }

}
