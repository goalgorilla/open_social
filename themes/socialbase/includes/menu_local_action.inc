<?php

/**
 * Implements hook_preprocess_menu_local_action().
 */
function socialbase_preprocess_menu_local_action(&$variables) {
  if (\Drupal::routeMatch()->getRouteName() === 'entity.group_content.collection') {
    $link_classes = $variables['link']['#options']['attributes']['class'];
    foreach ($link_classes as $delta => $class_name) {
      if (in_array($class_name, array('btn-xs', 'btn-default'))) {
        unset($link_classes[$delta]);
      }
    }
    $variables['link']['#options']['attributes']['class'] = $link_classes + array(
        'waves-effect',
        'waves-light',
        'btn-sm',
        'btn-primary',
        'btn-raised'
      );

    $variables['attributes']['class'][] = 'margin-bottom-l';
    $variables['attributes']['class'][] = 'margin-left-l';
  }
}
