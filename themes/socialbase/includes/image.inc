<?php

/**
 * Implements template_preprocess_image_style().
 */
function socialbase_preprocess_image_style(&$variables) {
  if ($variables['style_name'] == 'social_large') {
    $variables['image']['#attributes']['class'][] = 'img-circle';
  }
  if ($variables['style_name'] == 'social_medium') {
    $variables['image']['#attributes']['class'][] = 'img-medium';
    $variables['image']['#attributes']['class'][] = 'img-circle';
  }
  if ($variables['style_name'] == 'social_small') {
    $variables['image']['#attributes']['class'][] = 'img-small';
    $variables['image']['#attributes']['class'][] = 'img-circle';
  }

}
