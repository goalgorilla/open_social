<?php

namespace Drupal\social_node_statistics\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
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
   * The time.
   *
   * @var \Drupal\Component\Datetime\Time|TimeInterface
   */
  protected $time;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $matcher
   *   The route matcher.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time.
   */
  public function __construct(Connection $database, AccountProxyInterface $account, CurrentRouteMatch $matcher, TimeInterface $time) {
    $this->database = $database;
    $this->account = $account;
    $this->routeMatcher = $matcher;
    $this->time = $time;
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
      if ($this->routeMatcher->getRouteName() === 'entity.node.canonical') {
        $node = $this->routeMatcher->getParameter('node');
        if ($node instanceof NodeInterface) {
          $config = \Drupal::config('social_node_statistics.settings');
          // Check if we should log for this node bundle.
          if (in_array($node->bundle(), $config->get('node_types'), FALSE)) {
            $now = $this->time->getRequestTime();

            // Insert the event in the table.
            $this->database->insert('node_statistics')
              ->fields([
                'uid' => $this->account->id(),
                'nid' => $node->id(),
                'timestamp' => $now,
              ])
              ->execute();

            // Get latest timestamp for possible cache invalidation.
            $latest = $this->database->select('node_statistics', 'n')
              ->fields('n', ['timestamp'])
              ->condition('n.nid', $node->id())
              ->orderBy('n.timestamp', 'DESC')
              ->range('1', '1')
              ->execute()
              ->fetchField();

            // Get count of views for possible cache invalidation.
            $count = $this->database->select('node_statistics', 'n')
              ->condition('n.nid', $node->id())
              ->countQuery()
              ->execute()
              ->fetchField();

            // Clear render cache only if max age is reached and the count is of
            // a certain predefined number.
            if (($now - $latest) >= $config->get('max_age') && $count % $config->get('min_views') === 0) {
              Cache::invalidateTags(['node:' . $node->id() . ':views_count']);
            }
          }
        }
      }
    }
  }

}
