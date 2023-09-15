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
 *   - field: (optional) The existing field name should be an entity reference
 *     field to taxonomy terms from the "Content tags" vocabulary. Defaults to
 *     the 'social_tagging' field which is created by this module.
 *   - label: (optional) The human-readable name of the wrapper for the tags
 *     field(s) and it is the name for this field(s). Defaults to 'Tags'.
 *   - weight: (optional) The weight of the field. Defaults to 1.
 *   - group: (optional) The fields group name (the "group_" prefix is not
 *     needed). When the group with a defined name is absent then it will be
 *     created. Defaults to 'social_tags'.
 *   - wrapper: (optional) FALSE, if wrapper for tags field(s) should not have a
 *     label (tags field(s) will have label anyway). Defaults to TRUE.
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
 *   identifiers. The values are arrays of bundles or the "sets" array of an
 *   associative array(s). The "sets" item contains all configurations provided
 *   by the hook described above. When new bundles are added outside the "sets"
 *   item then these bundles will be moved to each sub-item of the "sets" item.
 *
 * @see \Drupal\social_tagging\SocialTaggingService::types()
 *
 * @ingroup social_tagging_api
 */
function hook_social_tagging_type_alter(array &$items) {
  $items['media']['sets'][0]['bundles'][] = 'photo';
}

/**
 * @} End of "addtogroup hooks".
 */
