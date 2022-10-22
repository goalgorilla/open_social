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
   *
   * @deprecated in social:11.5.0 and is removed from social:12.0.0. Use
   *   bundled node object itself `$event->isEnrollmentEnabled()` instead.
   * @see https://www.drupal.org/project/social/issues/3306568
   */
  public function isEnabled(NodeInterface $node);

}
