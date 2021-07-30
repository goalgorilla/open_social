<?php

/**
 * @file
 * Post update functions for Entity Access By Field.
 */

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10101_rebuild_node_access() {
  // Removing code as in favor of revert.
  // @see https://github.com/goalgorilla/open_social/pull/2438
}

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10102_rebuild_node_access() {
  node_access_rebuild(TRUE);
}
