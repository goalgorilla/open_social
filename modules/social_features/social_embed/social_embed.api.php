<?php

/**
 * @file
 * Hooks provided by the Social Embed module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the array of text formats with convert URLs filter.
 *
 * @param array $formats
 *   List of text formats where a key is filter name and if a value is TRUE then
 *   the current format will use the filter for converting URLs.
 *
 * @ingroup social_embed_api
 *
 * @see \Drupal\social_embed\SocialEmbedConfigOverrideBase::loadOverrides()
 */
function hook_social_embed_formats_alter(array &$formats) {
  $formats['basic_html'] = FALSE;
}

/**
 * @} End of "addtogroup hooks".
 */
