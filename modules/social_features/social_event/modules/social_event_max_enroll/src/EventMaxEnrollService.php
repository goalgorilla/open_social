<?php

namespace Drupal\social_event_max_enroll;

use Drupal\node\Entity\Node;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * Returns number of all enrollments per event.
   */
  public function getEnrollmentsNumber(Node $node) {
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
   * Returns number of left enrollments per event.
   */
  public function getEnrollmentsLeft(Node $node) {
    // Get max enrollment number.
    $max = $node->get('field_event_max_enroll_num')->value;
    // Take into account AN enrollments.
    $current = $this->getEnrollmentsNumber($node);
    // Count how many places left.
    return (int) $max - $current;
  }

  /**
   * Check if anonymous enrollment is allowed for given event.
   *
   * Returns TRUE if feature is enabled and node is event and max enroll is set.
   */
  public function isEnabled(Node $node) {
    $is_global_enabled = $this->configFactory->get('social_event_max_enroll.settings')->get('max_enroll');
    $is_event = $node->getType() === 'event';
    $is_event_max_enroll = isset($node->get('field_event_max_enroll_num')->value);
    return (bool) $is_global_enabled && $is_event && $is_event_max_enroll;
  }

}
