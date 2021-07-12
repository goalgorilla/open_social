<?php

namespace Drupal\social_node\Service;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\NodeInterface;

/**
 * Stores runtime messages sent out to individual users on the page.
 *
 * An example for these messages is for example: "Content X got saved".
 */
interface SocialNodeMessengerInterface extends MessengerInterface {

  /**
   * Defines the processing node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  public function setNode(NodeInterface $node);

}
