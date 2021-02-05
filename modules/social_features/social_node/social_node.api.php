<?php

/**
 * @file
 * Hooks specific to the Social Node module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provides message which will be displayed after saving a node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
 *   The message.
 *
 * @see \Drupal\social_node\Service\SocialNodeMessenger::addStatus()
 */
function hook_social_node_message(\Drupal\node\NodeInterface $node) {
  $t_args = [
    '@type' => node_get_type_label($node),
    '%title' => $node->toLink()->toString(),
  ];

  if ($node->isNew()) {
    return t('@type %title has been created.', $t_args);
  }

  return t('@type %title has been updated.', $t_args);
}

/**
 * @} End of "addtogroup hooks".
 */
