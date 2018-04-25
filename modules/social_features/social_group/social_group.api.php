<?php

/**
 * @file
 * Hooks provided by the Social_group module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to return node which was moved to another group.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The event or topic node.
 *
 * @ingroup social_group_api
 */
function hook_social_group_move(\Drupal\node\NodeInterface $node) {
  drupal_set_message(t('@title is moved.', ['@title' => $node->getTitle()]));
}

/**
 * @} End of "addtogroup hooks".
 */
