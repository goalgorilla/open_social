<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "activity" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("activity")
 */
class Activity extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Check if the view mode is one of the notification view modes.
    if (in_array($variables['content']['field_activity_output_text']['#view_mode'], [
      'notification',
      'notification_archive',
    ])) {
      // Remove href from output text.
      $variables['content']['field_activity_output_text'][0]['#text'] = strip_tags($variables['content']['field_activity_output_text'][0]['#text']);
      // Remove href from profile image.
      if (!empty($variables['actor'])) {
        if (is_object($variables['actor'])) {
          $variables['actor'] = $variables['actor']->getText();
        }
      }

    }

  }

}
