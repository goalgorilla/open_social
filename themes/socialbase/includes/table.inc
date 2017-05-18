<?php

/**
 * Implements template_preprocess_table().
 */
function socialbase_preprocess_table(&$variables) {
  $variables['card'] = TRUE;
  $files_field = 'edit-field-files';

  if (isset($variables['attributes']['id'])) {
    if ($variables['attributes']['id'] === 'field-event-managers-values') {
      $variables['card'] = FALSE;
    }

    if (!empty($variables['attributes']['id']) && (strpos($variables['attributes']['id'], $files_field) !== FALSE)) {
      $variables['attributes']['class'][] = 'tablesaw';
      $variables['attributes']['data-tablesaw-mode'] = 'stack';
      $variables['card'] = FALSE;
    }

  }

}
