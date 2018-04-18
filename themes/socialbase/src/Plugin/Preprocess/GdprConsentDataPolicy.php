<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "gdpr_consent_data_policy" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("gdpr_consent_data_policy")
 */
class GdprConsentDataPolicy extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables) {
    if (!\Drupal::request()->request->has('js')) {
      $variables->attributes['class'][] = 'card__body';
    }
  }

}
