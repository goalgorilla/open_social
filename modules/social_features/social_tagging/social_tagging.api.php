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
 * @return string|array
 *   The entity type identifier(s) or an associative array(s) of supported
 *   entity type configuration. The values are associative arrays that may
 *   contain the following elements:
 *   - entity_type: The entity type identifier.
 *   - bundles: (optional) The bundles list.
 *
 * @see \Drupal\social_tagging\SocialTaggingService::types()
 *
 * @ingroup social_tagging_api
 */
function hook_social_tagging_type() {
  return 'media';
}

/**
 * Alter configuration of supported entity types and bundles.
 *
 * @param array $items
 *   An associative array of supported entity types. The keys are entity type
 *   identifiers. The values are arrays of bundles.
 *
 * @see \Drupal\social_tagging\SocialTaggingService::types()
 *
 * @ingroup social_tagging_api
 */
function hook_social_tagging_type_alter(array &$items) {
  $items['media'][] = 'photo';
}

/**
 * @} End of "addtogroup hooks".
 */
