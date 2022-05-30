<?php

namespace Drupal\social_event_an_enroll;

use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;

/**
 * Class EventAnEnrollService.
 */
class EventAnEnrollService {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * EventAnEnrollService constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   Current route match.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   */
  public function __construct(AccountProxyInterface $account_proxy, CurrentRouteMatch $current_route_match, Connection $connection) {
    $this->currentUser = $account_proxy;
    $this->currentRouteMatch = $current_route_match;
    $this->database = $connection;
  }

  /**
   * Returns number of anonymous enrollments.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return int
   *   The number of anonymous event enrollments.
   */
  public function enrollmentCount($nid) {
    $query = $this->database
      ->select('event_enrollment__field_account', 'eefa');
    $query->join('event_enrollment__field_event', 'eefe', 'eefa.entity_id = eefe.entity_id');
    $query->condition('eefa.field_account_target_id', 0);
    $query->condition('eefe.field_event_target_id', $nid);

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Returns number of anonymous enrollments.
   *
   * @param string $token
   *   Token to validate.
   * @param int $nid
   *   The node ID.
   *
   * @return bool
   *   TRUE if token exists, FALSE otherwise.
   */
  public function tokenExists($token, $nid) {
    $query = $this->database
      ->select('event_enrollment__field_token', 'eeft');
    $query->join('event_enrollment__field_event', 'eefe', 'eeft.entity_id = eefe.entity_id');
    $query->condition('eeft.field_token_value', $token);
    $query->condition('eefe.field_event_target_id', $nid);

    $results = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    return !empty($results);
  }

  /**
   * Checks if a visitor is enrolled.
   *
   * @return bool
   *   Returns TRUE if the visitor is enrolled to this event, otherwise FALSE.
   */
  public function isEnrolled() {
    // Make sure the current user is anonymous.
    if (!$this->currentUser->isAnonymous()) {
      return FALSE;
    }

    // Get the token and Node ID from the route.
    $token = $this->currentRouteMatch->getParameter('token');
    $node = $this->currentRouteMatch->getParameter('node');

    // If some data is missing we can already return FALSE.
    if (empty($token) || !$node instanceof NodeInterface) {
      return FALSE;
    }

    // Check if the token is valid.
    return $this->tokenExists($token, (int) $node->id());
  }

}
