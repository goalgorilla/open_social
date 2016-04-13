<?php

/**
 * @file
 * Preprocessors for fieldgroup elements.
 */

/**
 * Prepares variables for horizontal tabs templates.
 *
 * Default template: horizontal-tabs.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 *
 */
function template_preprocess_horizontal_tabs(&$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup accordion templates.
 *
 * Default template: field-group-accordion.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the accordion element. Properties used: #children.
 *
 */
function template_preprocess_field_group_accordion(&$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup accordion item templates.
 *
 * Default template: field-group-accordion-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the accordion item element.
 *
 */
function template_preprocess_field_group_accordion_item(&$variables) {

  $element = $variables['element'];

  if (!empty($element['#title'])) {
    $variables['title'] = $element['#title'];
  }

  if (!empty($element['#description'])) {
    $variables['description'] = $element['#description'];
  }

  $variables['collapsed'] = $element['#collapsed'];
  $variables['label_attributes'] = new \Drupal\Core\Template\Attribute();
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}

/**
 * Prepares variables for fieldgroup html element templates.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 *
 */
function template_preprocess_field_group_html_element(&$variables) {

  $element = $variables['element'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = $element['#attributes'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}
