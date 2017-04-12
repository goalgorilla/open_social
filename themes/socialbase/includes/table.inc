<?php

/**
 * Implements template_preprocess_table().
 */
function socialbase_preprocess_table(&$variables) {

  if (isset($variables['attributes']['id']) && strpos($variables['attributes']['id'], 'edit-field-files')) !== FALSE)) {
    $variables['attributes']['class'][] = 'tablesaw';
    $variables['attributes']['data-tablesaw-mode'] = 'stack';
  }

}
