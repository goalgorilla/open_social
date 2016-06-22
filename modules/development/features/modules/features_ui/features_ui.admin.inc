<?php

/**
 * @file
 * Administration functions for features.module.
 */

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;

/**
 * Returns HTML for the features listing form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_features_listing(array $variables) {
  $form = $variables['form'];

  // Individual table headers.
  $rows = array();
  // Iterate through all the features, which are children of this element.
  foreach (Element::children($form) as $key) {
    // Stick the key into $module for easier access.
    $element = $form[$key];
    // Create the row for the table.
    $row = array();
    // Add the checkbox into the first cell.
    unset($element['enable']['#title']);

    $row[] = array('class' => array('checkbox'), 'data' => drupal_render($element['enable']));

    // Add the module label and expand/collapse functionalty.
    $id = Html::getUniqueId('feature-' . $key);
    $col2 = SafeMarkup::format('<label id="@id" for="@for" class="module-name table-filter-text-source">@name</label>',
      array(
        '@id' => $id,
        '@for' => $element['enable']['#id'],
        '@name' => drupal_render($element['name']),
      )
    );
    $row[] = array('class' => array('module'), 'data' => $col2);

    $row[] = array('class' => array('machine_name'), 'data' => drupal_render($element['machine_name']));

    $description = t('@details', array('@details' => drupal_render($element['details'])));
    /*
    $links = '';
    foreach (array('help', 'permissions', 'configure') as $link_type) {
      $links .= drupal_render($module['links'][$link_type]);
    }
    if ($links) {
      $description .= '  <div class="links">';
      $description .= $links;
      $description .= '</div>';
    }
    */
    $details = array(
      '#type' => 'details',
      '#title' => SafeMarkup::format('<span class="text">@desc</span>', array( '@desc' => drupal_render($element['description']))),
      '#attributes' => array('id' => $element['enable']['#id'] . '-description'),
      '#description' => $description,
    );
    $row[] = array(
      'class' => array('description', 'expand'),
      'data' => drupal_render($details),
    );
    $row[] = array(
      'class' => array('feature-version'),
      'data' => drupal_render($element['version']),
    );
    $row[] = array(
      'class' => array('feature-state'),
      'data' => drupal_render($element['state']),
    );

    $rows[] = array('data' => $row);
  }

  $table = array(
    '#type' => 'tableselect',
    '#header' => $form['#header'],
    '#options' => $rows,
    '#empty' => t('No Features packages available.'),
  );
  return drupal_render($table);
}

/**
 * Prepares variables for package assignment configuration form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_features_assignment_configure_form(&$variables) {
  $form =& $variables['form'];

  $header = array(
    t('Assignment method'),
    t('Description'),
    t('Enabled'),
    t('Weight'),
  );

  // If there is at least one operation enabled, show the operation column.
  if ($form['#show_operations']) {
    $header[] = t('Operations');
  }

  $table = array(
    '#type' => 'table',
    '#weight' => 5,
    '#header' => $header,
    '#attributes' => array('id' => 'features-assignment-methods'),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'assignment-method-weight',
      ),
    ),
  );

  foreach ($form['title'] as $id => $element) {
    // Do not take form control structures.
    if (is_array($element) && Element::child($id)) {
      $table[$id]['#attributes']['class'][] = 'draggable';
      $table[$id]['#weight'] = $element['#weight'];

      $table[$id]['title'] = array(
        '#prefix' => '<strong>',
        $form['title'][$id],
        '#suffix' => '</strong>',
      );
      $table[$id]['description'] = $form['description'][$id];
      $table[$id]['enabled'] = $form['enabled'][$id];
      $table[$id]['weight'] = $form['weight'][$id];
      if ($form['#show_operations']) {
        $table[$id]['operation'] = $form['operation'][$id];
      }
      // Unset to prevent rendering along with children.
      unset($form['title'][$id]);
      unset($form['description'][$id]);
      unset($form['enabled'][$id]);
      unset($form['weight'][$id]);
      unset($form['operation'][$id]);
    }
  }

  // For some reason, the #weight is not being handled by drupal_render.
  // So we remove the actions and then put them back into the form after the
  // table.
  $actions = $form['actions'];
  unset($form['actions']);
  $form['table'] = $table;
  $form['actions'] = $actions;
}

/**
 * Themes individual items in an item list.
 */
function theme_features_items(array $variables) {
  $items = $variables['items'];

  $list = array();
  foreach ($items as $item) {
    $class = !empty($item['class']) ? $item['class'] : '';
    $list[] = '<span class="features-item ' . $class . '" title="' . $item['name'] . '">' . $item['label'] . '</span>';
  }

  return '<span class="features-item-list">' . implode(' ', $list) . '</span>';
}

/**
 * Themes the assignment form.
 */
function theme_assignment_form(array $variables) {
  return drupal_render($variables['form']);
}
