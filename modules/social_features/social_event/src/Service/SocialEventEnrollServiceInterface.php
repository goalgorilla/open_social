<?php

namespace Drupal\social_event\Service;

use Drupal\node\NodeInterface;

/**
 * Interface SocialEventEnrollServiceInterface.
 *
 * @package Drupal\social_event\Service
 */
interface SocialEventEnrollServiceInterface {

  /**
   * Check if enrollment is allowed for given event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   *
   * @return bool
   *   TRUE if enrollment is allowed.
   */
  public function isEnabled(NodeInterface $node)

}
