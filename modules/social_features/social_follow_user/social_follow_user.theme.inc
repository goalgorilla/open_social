<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Prepares variables for followers templates.
 *
 * Default template: followers.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - flag: The flag object.
 *   - flaggable: The flaggable entity.
 */
function template_preprocess_followers(array &$variables): void {
  $variables['flag'] = \Drupal::entityTypeManager()->getStorage('flag')
    ->load('follow_user');
}
