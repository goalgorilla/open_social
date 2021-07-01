<?php

/**
 * @file
 * Post update functions for Entity Access By Field.
 */

/**
 * Rebuild node access.
 */
function entity_access_by_field_post_update_10101_rebuild_node_access() {
  node_access_rebuild(TRUE);
}
