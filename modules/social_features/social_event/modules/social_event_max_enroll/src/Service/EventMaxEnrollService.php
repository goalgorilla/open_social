<?php

namespace Drupal\social_event_max_enroll\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\Event;

/**
 * Class EventMaxEnrollService.
 *
 * @package Drupal\social_event_max_enroll\Service
 */
class EventMaxEnrollService implements EventMaxEnrollServiceInterface {

  /**
   * The event enrollment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EventMaxEnrollService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $configFactory
  ) {
    $this->storage = $entity_type_manager->getStorage('event_enrollment');
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnrollmentsNumber(NodeInterface $node) {
    return $this->storage->getQuery()
      ->condition('field_event', $node->id())
      ->condition('field_enrollment_status', 1)
      ->accessCheck()
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEnrollmentsLeft(NodeInterface $node) {
    // Get max enrollment number.
    $max = $node->get('field_event_max_enroll_num')->value;
    // Take into account AN enrollments.
    $current = $this->getEnrollmentsNumber($node);

    // Count how many spots are left, but never display less than 0.
    return $max >= $current ? $max - $current : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(NodeInterface $node) {
    // Check if we're working with an event.
    if ($node instanceof Event && $node->isEnrollmentEnabled()) {
      $config = $this->configFactory->get('social_event_max_enroll.settings');

      // Check if feature is enabled.
      if ($config->get('max_enroll')) {
        // Get enrollment configuration for this event.
        $is_event_max_enroll = !$node->get('field_event_max_enroll')->isEmpty() && $node->get('field_event_max_enroll')->value;
        $is_event_max_enroll_num = !$node->get('field_event_max_enroll_num')->isEmpty();

        return $is_event_max_enroll && $is_event_max_enroll_num;
      }
    }

    return FALSE;
  }

}
