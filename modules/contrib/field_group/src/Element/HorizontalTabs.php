<?php

/**
 * @file
 * Contains \Drupal\field_group\Element\HorizontalTabs.
 */

namespace Drupal\field_group\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for horizontal tabs.
 *
 * Formats all child details and all non-child details whose #group is
 * assigned this element's name as horizontal tabs.
 *
 * @FormElement("horizontal_tabs")
 */
class HorizontalTabs extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#default_tab' => '',
      '#process' => array(
        array($class, 'processHorizontalTabs'),
      ),
      '#theme_wrappers' => array('horizontal_tabs'),
    );
  }

  /**
   * Creates a group formatted as horizontal tabs.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param bool $on_form
   *   Are the tabs rendered on a form or not.
   *
   * @return array
   *   The processed element.
   */
  public static function processHorizontalTabs(&$element, FormStateInterface $form_state, $on_form = TRUE) {

    // Inject a new details as child, so that form_process_details() processes
    // this details element like any other details.
    $element['group'] = array(
      '#type' => 'details',
      '#theme_wrappers' => array(),
      '#parents' => $element['#parents'],
    );

    // Add an invisible label for accessibility.
    if (!isset($element['#title'])) {
      $element['#title'] = t('Horizontal Tabs');
      $element['#title_display'] = 'invisible';
    }

    // Add required JavaScript and Stylesheet.
    $element['#attached']['library'][] = 'field_group/formatter.horizontal_tabs';

    // Only add forms library on forms.
    if ($on_form) {
      $element['#attached']['library'][] = 'core/drupal.form';
    }

    // The JavaScript stores the currently selected tab in this hidden
    // field so that the active tab can be restored the next time the
    // form is rendered, e.g. on preview pages or when form validation
    // fails.
    $name = implode('__', $element['#parents']);
    if ($form_state->hasValue($name . '__active_tab')){
      $element['#default_tab'] = $form_state->getValue($name . '__active_tab');
    }
    $element[$name . '__active_tab'] = array(
      '#type' => 'hidden',
      '#default_value' => $element['#default_tab'],
      '#attributes' => array('class' => array('horizontal-tabs-active-tab')),
    );

    return $element;
  }

}
