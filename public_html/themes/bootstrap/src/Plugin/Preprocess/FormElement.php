<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\FormElement.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "form_element" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("form_element")
 */
class FormElement extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Variables $variables, $hook, array $info) {
    // Set errors flag.
    $variables['errors'] = $variables->element->hasProperty('has_error');

    if ($variables->element->getProperty('autocomplete_route_name')) {
      $variables['is_autocomplete'] = TRUE;
    }

    // See http://getbootstrap.com/css/#forms-controls.
    $checkbox = $variables['is_checkbox'] = $variables->element->isType('checkbox');
    $radio = $variables['is_radio'] = $variables->element->isType('radio');
    $variables['is_form_group'] = !$variables['is_checkbox'] && !$variables['is_radio'] && !$variables->element->isType(['hidden', 'textarea']);

    // Add label_display and label variables to template.
    $display = $variables['label_display'] = $variables['title_display'] = $variables->element->getProperty('title_display');

    // Place single checkboxes and radios in the label field.
    if (($checkbox || $radio) && $display !== 'none' && $display !== 'invisible') {
      $label = Element::create($variables['label']);
      $children = &$label->getProperty('children', '');
      $children .= $variables['children'];
      unset($variables['children']);

      // Pass the label attributes to the label, if available.
      if ($variables->element->hasProperty('label_attributes')) {
        $label->setAttributes($variables->element->getProperty('label_attributes'));
      }
    }

    // Remove the #field_prefix and #field_suffix values set in
    // template_preprocess_form_element(). These are handled on the input level.
    // @see \Drupal\bootstrap\Plugin\Preprocess\Input::preprocess().
    if ($variables->element->hasProperty('input_group') || $variables->element->hasProperty('input_group_button')) {
      $variables['prefix'] = FALSE;
      $variables['suffix'] = FALSE;
    }

  }

}
