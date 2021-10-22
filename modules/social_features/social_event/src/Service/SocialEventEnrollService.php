<?php

namespace Drupal\social_event\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Class SocialEventEnrollService.
 *
 * @package Drupal\social_event\Service
 */
class SocialEventEnrollService implements SocialEventEnrollServiceInterface {

  /**
   * The event settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $eventSettings;

  /**
   * SocialEventEnrollService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->eventSettings = $config_factory->get('social_event.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(NodeInterface $node) {
    if (
      $this->eventSettings->get('disable_event_enroll') ||
      $node->bundle() !== 'event' ||
      !$node->hasField('field_event_enroll') ||
      (!$node->get('field_event_enroll')->isEmpty() && !(bool) $node->get('field_event_enroll')->getString())
    ) {
      return FALSE;
    }

    $was_not_changed = $node->get('field_event_enroll')->isEmpty();
    $is_enabled = (bool) $node->get('field_event_enroll')->getString();

    // Make an exception for the invite enroll method.
    // This doesn't allow people to enroll themselves, but get invited.
    if (
      !$node->get('field_enroll_method')->isEmpty() &&
      (int) $node->get('field_enroll_method')->getString() === EventEnrollmentInterface::ENROLL_METHOD_INVITE
    ) {
      $is_enabled = TRUE;
    }

    return $was_not_changed || $is_enabled;
  }

}
