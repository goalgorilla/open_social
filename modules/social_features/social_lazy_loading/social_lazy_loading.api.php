<?php

/**
 * @file
 * Hooks provided by the Social Lazy Loading module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the array of text formats with lazy loading enabled.
 *
 * @param array $formats
 *   List of text formats where a key is filter name and if a value is TRUE then
 *   the current format will use the filter for converting URLs.
 *
 * @ingroup social_lazy_loading_api
 *
 * @see \Drupal\social_lazy_loading\SocialLazyLoadingTextFormatOverride::loadOverrides()
 */
function hook_social_lazy_loading_formats_alter(array &$formats) {
  $formats['basic_html'] = FALSE;
}

/**
 * @} End of "addtogroup hooks".
 */
