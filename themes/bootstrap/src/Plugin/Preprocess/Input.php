<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\Input.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "input" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("input")
 */
class Input extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    $variables->element->map(['id', 'name', 'value', 'type']);

    // Autocomplete.
    if ($route = $variables->element->getProperty('autocomplete_route_name')) {
      $variables['autocomplete'] = TRUE;
    }

    // Create variables for #input_group and #input_group_button flags.
    $variables['input_group'] = $variables->element->getProperty('input_group') || $variables->element->getProperty('input_group_button');

    // Map the element properties.
    $variables->map([
      'attributes' => 'attributes',
      'icon' => 'icon',
      'field_prefix' => 'prefix',
      'field_suffix' => 'suffix',
      'type' => 'type',
    ]);

    // Ensure attributes are proper objects.
    $this->preprocessAttributes($variables, $hook, $info);
  }

}
