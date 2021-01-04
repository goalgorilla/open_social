<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Template\Attribute;

/**
 * Pre-processes variables for the "details" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("details")
 */
class Details extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Do not display the details title in file upload widget.
    if (isset($variables['element']['#theme']) && $variables['element']['#theme'] == 'file_widget_multiple') {
      $variables['title'] = FALSE;
    }

    // Lets create body attributes.
    if ((isset($variables['body_attributes']) && !$variables['body_attributes'] instanceof Attribute) || !isset($variables['body_attributes'])) {
      $variables['body_attributes'] = new Attribute();
      $variables['body_attributes']->addClass('panel-body');
    }

  }

}
