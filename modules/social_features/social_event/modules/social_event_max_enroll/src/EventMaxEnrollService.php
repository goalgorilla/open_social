<?php

namespace Drupal\social_event_max_enroll;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Class EventMaxEnrollService.
 *
 * @package Drupal\social_event_max_enroll
 */
class EventMaxEnrollService {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EventMaxEnrollService constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Injection of the entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $configFactory) {
    $this->database = $database;
    $this->configFactory = $configFactory;
  }

  /**
   * Get count of enrollments per event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return int
   *   The enrollments count.
   */
  public function getEnrollmentsNumber(NodeInterface $node) {
    $query = $this->database->select('event_enrollment__field_enrollment_status', 'eefes');
    $query->join('event_enrollment__field_event', 'eefe', 'eefes.entity_id = eefe.entity_id');
    $query->condition('eefe.field_event_target_id', $node->id());
    $query->condition('eefes.field_enrollment_status_value', 1);

    return (int) $query
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Get number of enrollments still possible per event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return int
   *   How many spots are left.
   */
  public function getEnrollmentsLeft(NodeInterface $node) {
    // Get max enrollment number.
    $max = $node->get('field_event_max_enroll_num')->value;
    // Take into account AN enrollments.
    $current = $this->getEnrollmentsNumber($node);

    // Count how many spots are left, but never display less than 0.
    return ($max - $current) >= 0 ? ($max - $current) : 0;
  }

  /**
   * Check if anonymous enrollment is allowed for given event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return bool
   *   Returns TRUE if feature is enabled, the node is an event and max enroll
   *   is configured.
   */
  public function isEnabled(NodeInterface $node) {
    // Check if feature is enabled.
    $is_global_enabled = $this->configFactory->get('social_event_max_enroll.settings')->get('max_enroll');

    // Check if we're working with an event.
    $is_event = $node->getType() === 'event';

    // Get enrollment configuration for this event.
    $is_event_max_enroll = !empty($node->get('field_event_max_enroll')->value);
    $is_event_max_enroll_num = isset($node->get('field_event_max_enroll_num')->value);

    return (bool) $is_global_enabled && $is_event && $is_event_max_enroll && $is_event_max_enroll_num;
  }

}
