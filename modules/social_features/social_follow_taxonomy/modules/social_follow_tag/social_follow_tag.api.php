<?php

/**
 * @file
 * Social Follow Tag API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on the available vocabularies.
 *
 * @param string[] $bundles
 *   Array taxonomy vocabulary IDs.
 */
function hook_social_follow_tag_vocabulary_list_alter(array &$bundles): void {
  $bundles[] = 'social_tagging';
}

/**
 * @} End of "addtogroup hooks".
 */
