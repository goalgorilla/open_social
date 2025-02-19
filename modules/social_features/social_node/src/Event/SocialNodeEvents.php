<?php

declare(strict_types=1);

namespace Drupal\social_node\Event;

/**
 * Defines events for the current module.
 */
final class SocialNodeEvents {

  /**
   * Name of the event fired on social node query conditions build.
   *
   * @Event
   *
   * @see \Drupal\social_node\Event\NodeQueryAccessEvent
   */
  const NODE_QUERY_ACCESS_ALTER = 'social_node.query_access_alter';

}
