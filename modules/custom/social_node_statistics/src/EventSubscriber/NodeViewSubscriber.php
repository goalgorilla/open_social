<?php

namespace Drupal\social_node_statistics\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class NodeViewSubscriber.
 *
 * @package Drupal\social_node_statistics\EventSubscriber
 */
class NodeViewSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The route matcher.
   *
   * @var \Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher
   */
  protected $routeMatcher;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $matcher
   *   The route matcher.
   */
  public function __construct(Connection $database, AccountProxyInterface $account, CurrentRouteMatch $matcher) {
    $this->database = $database;
    $this->account = $account;
    $this->routeMatcher = $matcher;
  }

  /**
   * Get the request events.
   *
   * @return mixed
   *   Returns request events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['trackNodeView'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::TERMINATE event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event.
   *
   * @throws \Exception
   */
  public function trackNodeView(PostResponseEvent $event) {
    // If the response is a success (e.g. status code 200) we can proceed.
    if ($event->getResponse()->isSuccessful()) {
      // Check if we're on a node page.
      $node = $this->routeMatcher->getParameter('node');
      if ($node instanceof NodeInterface) {
        // @todo: get this from settings page.
        $bundles = ['event', 'topic'];

        // Check if we should log for this node bundle.
        if (in_array($node->bundle(), $bundles, FALSE)) {
          // Insert the event in the table.
          $this->database->insert('node_statistics')
            ->fields([
              'uid' => $this->account->id(),
              'nid' => $node->id(),
              'timestamp' => $event->getResponse()->getDate()->getTimestamp(),
            ])
            ->execute();
        }
      }
    }
  }

}
