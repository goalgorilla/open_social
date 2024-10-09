<?php

declare(strict_types=1);

namespace Drupal\social_node\Event;

/**
 * Defines events for the current module.
 */
final class SocialNodeEvents {

  /**
   * Name of the event fired after conditions by visibility are build.
   *
   * @Event
   *
   * @see \Drupal\social_node\Event\NodeQueryAccessEvent
   */
  const NODE_ACCESS_QUERY_ALTER = 'social_node.query_access_alter';

}
