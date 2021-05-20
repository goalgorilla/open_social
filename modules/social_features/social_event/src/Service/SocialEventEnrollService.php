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
      !$node->hasField('field_event_enable_enrollment') ||
      $node->get('field_event_enable_enrollment')->isEmpty() ||
      !(bool) $node->get('field_event_enable_enrollment')->first()->getValue()['value']
    ) {
      return FALSE;
    }
    if ($node->bundle() === 'event' && $node->hasField('field_event_enroll')) {
      $was_not_changed = $node->get('field_event_enroll')->isEmpty();
      $is_enabled = $node->get('field_event_enroll')->first()->getValue()['value'];

      // Make an exception for the invite enroll method.
      // This doesn't allow people to enroll themselves, but get invited.
      if (!$node->get('field_enroll_method')->isEmpty() && (int) $node->get('field_enroll_method')->first()->getValue()['value'] === EventEnrollmentInterface::ENROLL_METHOD_INVITE) {
        $is_enabled = TRUE;
      }

      return $was_not_changed || $is_enabled;
    }

    return FALSE;
  }

}
