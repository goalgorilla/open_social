<?php

/**
 * @file
 * Hooks provided by the improved_theme_settings module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to add additional CSS based on theme settings.
 *
 * @param string $theme
 *   The name of the them that's being rendered.
 *
 * @return string
 *   Returns a string that will be added as CSS.
 *
 * @ingroup improved_theme_settings
 */
function hook_improved_theme_settings_add(string $theme) {
  $style_to_add = '';

  $card_radius = improved_theme_settings_get_setting('card_radius', $theme);

  if ($card_radius >= 0) {
    $style_to_add .= '
      .my-custom-selector {
        border-radius: ' . $card_radius . 'px;
      }
    ';
  }

  return $style_to_add;
}

/**
 * @} End of "addtogroup hooks".
 */
