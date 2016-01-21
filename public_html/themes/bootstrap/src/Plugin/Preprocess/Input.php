<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\Input.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Utility\Element;
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

      // Use an icon for autocomplete "throbber".
      $icon = Bootstrap::glyphicon('refresh', [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['ajax-progress', 'ajax-progress-throbber', 'invisible'],
        ],
        'throbber' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => ['throbber']],
        ],
      ]);

      $variables->element->setProperty('input_group', TRUE);
      $variables->element->setProperty('field_suffix', $icon);
    }

    // Create variables for #input_group and #input_group_button flags.
    $variables['input_group'] = $variables->element->getProperty('input_group') || $variables->element->getProperty('input_group_button');
    if ($variables['input_group']) {
      $input_group_attributes = ['class' => ['input-group-' . ($variables->element->getProperty('input_group_button') ? 'btn' : 'addon')]];
      if ($prefix = $variables->element->getProperty('field_prefix')) {
        $variables->element->setProperty('field_prefix', [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => $input_group_attributes,
          '#value' => Element::create($prefix)->render(),
          '#weight' => -1,
        ]);
      }
      if ($suffix = $variables->element->getProperty('field_suffix')) {
        $variables->element->setProperty('field_suffix', [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => $input_group_attributes,
          '#value' => Element::create($suffix)->render(),
          '#weight' => 1,
        ]);
      }
    }

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
