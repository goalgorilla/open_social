<?php

namespace Drupal\social_event_max_enroll\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Service\SocialEventEnrollServiceInterface;

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
   * The social event enroll.
   *
   * @var \Drupal\social_event\Service\SocialEventEnrollServiceInterface
   */
  protected $socialEventEnroll;

  /**
   * EventMaxEnrollService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   * @param \Drupal\social_event\Service\SocialEventEnrollServiceInterface $social_event_enroll
   *   The social event enroll.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $configFactory,
    SocialEventEnrollServiceInterface $social_event_enroll
  ) {
    $this->storage = $entity_type_manager->getStorage('event_enrollment');
    $this->configFactory = $configFactory;
    $this->socialEventEnroll = $social_event_enroll;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnrollmentsNumber(NodeInterface $node): int {
    return $this->storage->getQuery()
      ->condition('field_event', $node->id())
      ->condition('field_enrollment_status', 1)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEnrollmentsLeft(NodeInterface $node): int {
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
  public function isEnabled(NodeInterface $node): bool {
    // Check if we're working with an event.
    if ($this->socialEventEnroll->isEnabled($node)) {
      $config = $this->configFactory->get('social_event_max_enroll.settings');

      // Check if feature is enabled.
      if ($config->get('max_enroll')) {
        // Get enrollment configuration for this event.
        $is_event_max_enroll = !$node->field_event_max_enroll->isEmpty();
        $is_event_max_enroll_num = !$node->field_event_max_enroll_num->isEmpty();

        return $is_event_max_enroll && $is_event_max_enroll_num;
      }
    }

    return FALSE;
  }

}
