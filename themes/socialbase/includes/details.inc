<?php

/**
 * Implements hook_preprocess_details().
 */
function socialbase_preprocess_details(&$variables) {
  // Do not display the details title in file upload widget
  if (isset($variables['element']['#theme']) && $variables['element']['#theme'] == 'file_widget_multiple') {
    $variables['title'] = FALSE;
  }
}
