<?php

use Drupal\Component\Utility\Html as HtmlUtility;


/**
 * Implements hook_preprocess_form_element().
 */
function socialbase_preprocess_form_element(&$variables) {

  // Tell the label for a switch form element it is in that switch form element
  $switch_elements = ['email_notifications', 'profile_privacy'];
  if (array_intersect($switch_elements, $variables['element']['#array_parents'])) {
    $variables['label']['#switch'] = TRUE;
  }

  // Use cards for the vertical tabs component
  if ($variables['element']['#type'] === 'vertical_tabs') {
    $variables['attributes']['class'][] = 'card';
  }

}


/**
 * Implements hook_preprocess_form_element_label().
 */
function socialbase_preprocess_form_element_label(&$variables) {
  if (isset($variables['element']['#id'])) {
    if (strpos($variables['element']['#id'], 'field-visibility') !== FALSE) {
      if (isset($variables['element']['#attributes']['title'])) {
        $description = $variables['element']['#attributes']['title'];
        $variables['element']['#attributes'] = [];
        $variables['description'] = $description;
      }
      // Set the materialize icon.
      $variables['material_icon'] = _socialbase_get_visibility_icon($variables['element']['#title']);
    }
  }
}


/**
* Implements hook_theme_suggestions_form_element_alter().
*/
function socialbase_theme_suggestions_form_element_alter(array &$suggestions, array $variables) {

  // Lets add the form element parent to the theme suggestions.
  if (isset($variables['element']['#parents'][0])) {
    $hook = HtmlUtility::escape($variables['element']['#parents'][0]);
    $suggestions[] = $variables['theme_hook_original'] . '__' . $hook;
  }

  $switch_elements = ['email_notifications', 'profile_privacy'];
  if (array_intersect($switch_elements, $variables['element']['#array_parents'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__switch';
  }
}


/**
 * Implements hook_theme_suggestions_form_element_label_alter().
 */
function socialbase_theme_suggestions_form_element_label_alter(array &$suggestions, array $variables) {
  // Lets add the form element parent to the theme suggestions.
  if (isset($variables['element']['#id'])) {
    if (strpos($variables['element']['#id'], 'field-visibility') !== FALSE) {
      $suggestions[] = $variables['theme_hook_original'] . '__' . 'dropdown';
    }
  }

  if (isset($variables['element']['#switch']) && $variables['element']['#switch'] == TRUE) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . 'switch';
  }
}


/**
 * Implements hook_preprocess_file_widget_multiple().
 */
function socialbase_preprocess_file_widget_multiple(&$variables) {
  // Remove duplicated ajax wrapper for topic/events files field,
  // because one is already rendered in container.html.twig
  if (!empty($variables['element']['#id']) && (strpos($variables['element']['#id'], 'edit-field-files') !== FALSE)) {
    unset($variables['element']['#prefix']);
    unset($variables['element']['#suffix']);
  }
}