<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "data_policy_data_policy" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("data_policy_data_policy")
 */
class DataPolicyDataPolicy extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables) {
    if (!\Drupal::request()->request->has('js')) {
      $variables->attributes['class'][] = 'card__body';
    }
  }

}
