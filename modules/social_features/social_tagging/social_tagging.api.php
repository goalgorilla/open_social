<?php

/**
 * @file
 * Hooks provided by the Social Tagging module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide entity type(s) which is(are) supported content tags.
 *
 * @return string|string[]
 *   The entity type identifier(s).
 *
 * @see social_tagging_entity_base_field_info()
 * @see social_tagging_update_11501()
 *
 * @ingroup social_tagging_api
 */
function hook_social_tagging_type() {
  return 'media';
}

/**
 * @} End of "addtogroup hooks".
 */
