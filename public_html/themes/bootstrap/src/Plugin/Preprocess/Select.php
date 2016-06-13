<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\Input.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "select" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("select")
 */
class Select extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    // Create variables for #input_group and #input_group_button flags.
    $variables['input_group'] = $variables->element->getProperty('input_group') || $variables->element->getProperty('input_group_button');

    // Map the element properties.
    $variables->map([
      'attributes' => 'attributes',
      'field_prefix' => 'prefix',
      'field_suffix' => 'suffix',
    ]);

    // Ensure attributes are proper objects.
    $this->preprocessAttributes($variables, $hook, $info);
  }

}
